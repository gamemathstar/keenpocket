@extends('layout.app')


@section('content')

    <div class="row">

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Agent  List</h4>
                    <a href="{{route('agent.edit')}}" class="btn btn-outline-info">Add New Agent</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="example3_wrapper" class="dataTables_wrapper no-footer">
                            <div id="example3_filter" class="dataTables_filter">
                                <label>
{{--                                    Search: --}}
{{--                                    <input type="search" class="" placeholder="" aria-controls="example3">--}}
                                </label>
                            </div>
                            <table id="example3" class="display dataTable no-footer" style="min-width: 845px"
                                   role="grid" aria-describedby="example3_info">
                                <thead>
                                <tr role="row">
                                    <th class="sorting_asc" tabindex="0" aria-controls="example3" rowspan="1"
                                        colspan="1" aria-sort="ascending"
                                        aria-label=": activate to sort column descending" style="width: 19px;"></th>
                                    <th class="sorting" tabindex="0" aria-controls="example3" rowspan="1" colspan="1"
                                        aria-label="Name: activate to sort column ascending" style="width: 98.3984px;">
                                        Name
                                    </th>
                                    <th class="sorting" tabindex="0" aria-controls="example3" rowspan="1" colspan="1"
                                        aria-label="Department: activate to sort column ascending"
                                        style="width: 121.125px;">LGA
                                    </th>
                                    <th class="sorting" tabindex="0" aria-controls="example3" rowspan="1" colspan="1"
                                        aria-label="Gender: activate to sort column ascending"
                                        style="width: 49.0156px;">Gender
                                    </th>
                                    <th class="sorting" tabindex="0" aria-controls="example3" rowspan="1" colspan="1"
                                        aria-label="Education: activate to sort column ascending"
                                        style="width: 93.4375px;">Ward
                                    </th>
                                    <th class="sorting" tabindex="0" aria-controls="example3" rowspan="1" colspan="1"
                                        aria-label="Mobile: activate to sort column ascending"
                                        style="width: 77.3359px;">Mobile
                                    </th>
                                    <th class="sorting" tabindex="0" aria-controls="example3" rowspan="1" colspan="1"
                                        aria-label="Email: activate to sort column ascending" style="width: 112.023px;">
                                        Email
                                    </th>
                                    <th class="sorting" tabindex="0" aria-controls="example3" rowspan="1" colspan="1"
                                        aria-label="Joining Date: activate to sort column ascending"
                                        style="width: 82.5938px;">Polling Unit
                                    </th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($agents as $agent)
                                <tr role="row" class="odd">
                                    <td class="sorting_1">
                                        <img class="rounded-circle" width="35"
                                                               src="images/profile/small/pic1.jpg" alt="">
                                    </td>
                                    <td>{{$agent->full_name}}</td>
                                    <td>{{$agent->lga}}</td>
                                    <td>{{$agent->gender}}</td>
                                    <td>{{$agent->ward}}</td>
                                    <td>{{$agent->phone_number}}</td>
                                    <td>{{$agent->email}}</td>
                                    <td>{{$agent->unit}}</td>
                                </tr>
                                @endforeach
                                <
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
