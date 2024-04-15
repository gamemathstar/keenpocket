<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\Ward;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    //

    public function index(Request $request)
    {
        $agents = Agent::join("wards","wards.id","=","agents.ward_id")
            ->join("lgas","lgas.id","=","agents.lga_id")
            ->join("polling_units","polling_units.id","=","agents.polling_unit_id")
            ->select(["agents.*","lgas.name as lga","wards.name as ward","polling_units.name as unit"])
            ->get();
        return view('partials.agents.index',compact('agents'));
    }

    public function edit(Request $request)
    {
        $lgas = Lga::where('state_id',36)->get();
        return view('partials.agents.edit',compact('lgas'));
    }


    public function loadWard(Request $request)
    {
        $wards = Ward::where('lga_id',$request->id)->get();
        $str = "<option value=''>Choose</option>\r";
        foreach ($wards as $ward){
            $str .=  "<option value='{$ward->id}'>{$ward->name}</option>\r";
        }
        return $str;
    }
    public function loadUnit(Request $request)
    {
        $wards = PollingUnit::where('ward_id',$request->id)->get();
        $str = "<option value=''>Choose</option>\r";
        foreach ($wards as $ward){
            $str .=  "<option value='{$ward->id}'>{$ward->name}</option>\r";
        }
        return $str;
    }

    public function save(Request $request)
    {
        $this->validate($request,[
            "full_name"=>"required",
            "email"=>"required",
            "phone_number"=>"required|unique:agents,email"
        ]);

        $agent = new Agent();
        $agent->full_name = $request->full_name;
        $agent->email = $request->email;
        $agent->phone_number = $request->phone_number;
        $agent->gender = $request->gender;
        $agent->lga_id = $request->lga_id;
        $agent->ward_id = $request->ward_id;
        $agent->polling_unit_id = $request->unit_id;
        $agent->save();
        return back();
    }
}
