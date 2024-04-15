@extends('layout.app')


@section('content')
    <div class="row">

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Edit/Add Agent</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <form method="post" action="{{route('agent.save')}}">
                            @csrf
                            <div class="form-row">

                                <div class="form-group col-md-4">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control" placeholder="Full Name" name="full_name" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Email</label>
                                    <input type="email" class="form-control" placeholder="Email" name="email" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Phone No.</label>
                                    <input type="text" class="form-control" placeholder="Phone Number" name="phone_number" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Gender</label>
                                    <select class="mr-sm-2 form-control" name="gender" required>
                                        <option selected>Choose...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>L.G.A</label>
                                    <select class="mr-sm-2 form-control" name="lga_id" id="lga" required>
                                        <option selected>Choose...</option>
                                        @foreach($lgas as $lga)
                                            <option value="{{$lga->id}}">{{$lga->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Ward</label>
                                    <select class="form-control" id="ward" name="ward_id" required>
                                        <option selected>Choose...</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Polling Unit</label>
                                    <select class="form-control" id="unit" name="unit_id" required>
                                        <option selected>Choose...</option>

                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{asset('jquery-1.10.2.js')}}"></script>
    <script>

        $(function (){
            $("#lga").on("change",function(){
                $.ajax({
                    url:"{{route("ajax.load.ward")}}",
                    type:"GET",
                    data:{id:$("#lga").val()},
                    success:function (data){
                        $("#ward").html(data);
                    }
                });
            });
            $("#ward").on("change",function(){
                $.ajax({
                    url:"{{route("ajax.load.unit")}}",
                    type:"GET",
                    data:{id:$("#ward").val()},
                    success:function (data){
                        $("#unit").html(data);
                    }
                });
            });
        });
    </script>
@endsection
