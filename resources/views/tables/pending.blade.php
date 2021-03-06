@extends('layouts.master')

    @component('/tables/css')
    @endcomponent

@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="hpanel">
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover data-table" >
                            <thead>
                                <tr>
                                    <th>Count</th>
                                    <th>Lab Code </th>
                                    <th>Sample Code</th>
                                    <th>Batch No  </th>
                                    <th>Worksheet No</th>
                                    <th>Facility</th>
                                    <th>County</th>
                                    @if(Session('testingSystem')=='Viralload')
                                        <th>Sample Type</th>
                                    @endif
                                    <th>Date Collected</th>
                                    <th>Date Received</th>
                                    <th>Received Status</th>
                                    <th>Waiting Time (days)</th>
                                    <th>Task</th>
                                </tr>
                            </thead>
                            <tbody> 
                            @forelse($samples as $key => $sample)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>{{ $sample->id }}</td>
                                    <td>{{ $sample->patient }}</td>
                                    <td>{{ $sample->batch_id }}</td>
                                    <td>{{ $sample->worksheet_id ?? 'Not in worksheet' }}</td>
                                    <td>{{ $sample->facility }}</td>
                                    <td>{{ $sample->county }}</td>
                                    @if(Session('testingSystem')=='Viralload')
                                        <td>{{ $sample->sampletype }}</td>
                                    @endif
                                    <td>{{ $sample->datecollected }}</td>
                                    <td>{{ $sample->datereceived }}</td>
                                    <td>{{ $sample->receivedstatus }}</td>
                                    <td>{{ $sample->waitingtime }}</td>
                                    @if(Session('testingSystem')=='Viralload')
                                        <td>
                                            <a href="{{ url('/viralsample/' . $sample->id . '/edit') }}">View Details</a> | 
                                            <a href="{{ url('/viralsample/' . $sample->id . '/edit') }}">Edit</a> | 
                                            <a href="{{ url('/viralsample/release/'. $sample->id) }}">Release as Redraw</a>
                                        </td>
                                    @else
                                        <td>
                                            <a href="{{ url('/sample/' . $sample->id . '/edit') }}">View Details</a> | 
                                            <a href="{{ url('/sample/' . $sample->id . '/edit') }}">Edit</a> | 
                                            <a href="{{ url('/sample/release/'. $sample->id) }}">Release as Redraw</a>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <td colspan="12"><center>No Data Available</center></td>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@section('scripts') 

    @component('/tables/scripts')

    @endcomponent

@endsection