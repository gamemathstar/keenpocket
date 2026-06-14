<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FeeItem;
use App\Models\PaymentPlan;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolPayment;
use App\Models\Student;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    public function create()
    {
        abort_unless(config('school.enabled', true), 404);
        abort_unless(auth()->user()->canCreateSchool(), 403, 'Ask the KeenPocket team to enable school creation for your account.');
        if ($existing = auth()->user()->school) {
            return redirect()->route('school.show', $existing->id); // one school per owner
        }

        return view('school.create');
    }

    public function store(Request $request)
    {
        abort_unless(config('school.enabled', true), 404);
        abort_unless(auth()->user()->canCreateSchool(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'bank' => 'nullable|string|max:255',
            'nuban' => 'nullable|string|max:32',
            'account_name' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'background_image' => 'nullable|image|max:4096',
        ]);

        $coins = app(\App\Services\Coins\CoinService::class);
        $cost = $coins->cost('school');
        if (!$coins->canAfford(auth()->user(), $cost)) {
            return back()->withErrors(['name' => "Creating a school costs {$cost} Keens — you have {$coins->balance(auth()->user())}."])->withInput();
        }

        $school = new School($request->only(['name', 'address', 'contact', 'bank', 'nuban', 'account_name']));
        $school->owner_id = auth()->id();
        if ($request->hasFile('logo')) {
            $school->logo = $request->file('logo')->store('schools', 'public');
        }
        if ($request->hasFile('background_image')) {
            $school->background_image = $request->file('background_image')->store('schools', 'public');
        }
        $school->save();

        $coins->charge(auth()->user(), $cost, 'Create school: '.$school->name);

        return redirect()->route('school.show', $school->id)->with('status', 'School created.'.($cost ? " ({$cost} Keens)" : ''));
    }

    public function show($id)
    {
        $school = School::findOrFail($id);
        abort_unless($school->owner_id == auth()->id(), 403, 'Only the school owner can manage it.');

        $classes = $school->classes()->withCount('students')->orderBy('name')->get();
        $students = Student::where('students.school_id', $school->id)
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->leftJoin('users', 'users.id', '=', 'students.parent_id')
            ->select('students.*', 'school_classes.name as class_name', 'users.name as parent_name', 'users.phone_number as parent_phone')
            ->orderBy('users.name')->get();

        return view('school.show', compact('school', 'classes', 'students'));
    }

    public function storeClass(Request $request, $id)
    {
        $school = $this->ownedSchool($id);
        $data = $request->validate(['name' => 'required|string|max:255']);
        $school->classes()->create(['name' => $data['name']]);

        return back()->with('status', 'Class added.');
    }

    public function storeFeeItem(Request $request, $id)
    {
        $school = $this->ownedSchool($id);
        $data = $request->validate([
            'school_class_id' => 'required|integer',
            'term' => 'required|integer|min:1|max:3',
            'name' => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
        ]);
        abort_unless($school->classes()->whereKey($data['school_class_id'])->exists(), 422);

        FeeItem::create([
            'school_id' => $school->id, 'school_class_id' => $data['school_class_id'],
            'term' => $data['term'], 'name' => $data['name'], 'amount' => $data['amount'],
        ]);

        return back()->with('status', 'Fee item added.');
    }

    public function addStudent(Request $request, $id)
    {
        $school = $this->ownedSchool($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'school_class_id' => 'nullable|integer',
            'parent_phone' => 'required|string|max:20',
            'parent_name' => 'nullable|string|max:255',
        ]);

        $parent = User::where('phone_number', $data['parent_phone'])->first();
        if (!$parent) {
            if (empty($data['parent_name'])) {
                return back()->withErrors(['parent_name' => 'Enter the parent\'s name (they are not on KeenPocket yet).'])->withInput();
            }
            $parent = User::create([
                'name' => $data['parent_name'], 'email' => $data['parent_phone'],
                'username' => $data['parent_phone'], 'phone_number' => $data['parent_phone'],
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        Student::create([
            'school_id' => $school->id,
            'school_class_id' => $data['school_class_id'] ?: null,
            'parent_id' => $parent->id, 'name' => $data['name'],
        ]);

        return back()->with('status', $data['name'].' added under '.$parent->name.'.');
    }

    public function setPlan(Request $request, $id)
    {
        $school = $this->ownedSchool($id);
        $data = $request->validate([
            'student_id' => 'required|integer',
            'mode' => 'required|in:percent,min_monthly',
            'percent' => 'nullable|in:100,50,30',
            'min_monthly' => 'nullable|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);
        abort_unless(Student::where(['id' => $data['student_id'], 'school_id' => $school->id])->exists(), 422);

        PaymentPlan::updateOrCreate(
            ['student_id' => $data['student_id'], 'status' => 'ACTIVE'],
            [
                'school_id' => $school->id, 'mode' => $data['mode'],
                'percent' => $data['mode'] === 'percent' ? ($data['percent'] ?? 100) : null,
                'min_monthly' => $data['mode'] === 'min_monthly' ? $data['min_monthly'] : null,
                'note' => $data['note'] ?? null,
            ]
        );

        return back()->with('status', 'Payment plan saved.');
    }

    public function recordPayment(Request $request, $id)
    {
        $school = $this->ownedSchool($id);
        $data = $request->validate([
            'student_id' => 'required|integer',
            'term' => 'required|integer|min:1|max:3',
            'amount' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);
        abort_unless(Student::where(['id' => $data['student_id'], 'school_id' => $school->id])->exists(), 422);

        SchoolPayment::create([
            'school_id' => $school->id, 'student_id' => $data['student_id'], 'term' => $data['term'],
            'amount' => $data['amount'], 'note' => $data['note'] ?? null, 'recorded_by' => auth()->id(),
        ]);

        return back()->with('status', 'Payment recorded.');
    }

    /** Parent dashboard: my children, their term fees, paid vs pending. */
    public function children()
    {
        $kids = Student::where('students.parent_id', auth()->id())
            ->join('schools', 'schools.id', '=', 'students.school_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->select('students.*', 'schools.name as school_name', 'school_classes.name as class_name')
            ->orderBy('schools.name')->get();

        // Per child: each term's fee, paid and pending.
        $rows = $kids->map(function ($s) {
            $class = $s->school_class_id ? SchoolClass::find($s->school_class_id) : null;
            $student = Student::find($s->id);
            $terms = [];
            foreach ([1, 2, 3] as $t) {
                $fee = $class ? $class->termFee($t) : 0;
                $paid = $student->paidForTerm($t);
                $terms[$t] = ['fee' => $fee, 'paid' => $paid, 'pending' => max(0, $fee - $paid)];
            }
            return (object) [
                'student' => $s, 'school' => $s->school_name, 'class' => $s->class_name,
                'plan' => $student->plan, 'terms' => $terms,
            ];
        });

        return view('school.children', compact('rows'));
    }

    private function ownedSchool($id): School
    {
        $school = School::findOrFail($id);
        abort_unless($school->owner_id == auth()->id(), 403, 'Only the school owner can do this.');

        return $school;
    }
}
