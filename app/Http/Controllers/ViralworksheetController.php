<?php

namespace App\Http\Controllers;

use App\Viralworksheet;
use App\Viralsample;
use App\User;
use App\Misc;
use DB;
use Excel;
use Illuminate\Http\Request;

class ViralworksheetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $state = session()->pull('viral_worksheet_state', null);
        // $worksheets = Viralworksheet::with(['creator', 'sample'])
        // ->when($state, function ($query) use ($state){
        //     return $query->where('status_id', $state);
        // })
        // ->get();

        $worksheets = Viralworksheet::selectRaw('viralworksheets.*, count(viralsamples.id) AS samples_no, users.surname, users.oname')
            ->join('viralsamples', 'viralsamples.worksheet_id', '=', 'viralworksheets.id')
            ->join('users', 'users.id', '=', 'viralworksheets.createdby')
            // ->leftJoin('worksheetstatus', 'worksheetstatus.id', '=', 'viralworksheets.status_id') , worksheetstatus.state
            ->when($state, function ($query) use ($state){
                return $query->where('status_id', $state);
            })
            ->groupBy('viralworksheets.id')
            ->get();

        $statuses = collect($this->wstatus());
        $machines = collect($this->wmachine());
        // dd($statuses->where('status', 1)[0]);
        // dd($worksheets);


        return view('tables.viralworksheets', ['worksheets' => $worksheets, 'statuses' => $statuses, 'machines' => $machines]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $samples = Viralsample::selectRaw("viralsamples.*, viralpatients.patient, view_facilitys.name, viralbatches.datereceived, viralbatches.high_priority, IF(parentid > 0 OR parentid IS NULL, 0, 1) AS isnull")
            ->join('viralbatches', 'viralsamples.batch_id', '=', 'viralbatches.id')
            ->join('viralpatients', 'viralsamples.patient_id', '=', 'viralpatients.id')
            ->leftJoin('view_facilitys', 'view_facilitys.id', '=', 'viralbatches.facility_id')
            ->whereYear('datereceived', '>', 2014)
            ->where('inworksheet', 0)
            ->where('input_complete', true)
            ->whereIn('receivedstatus', [1, 3])
            ->whereRaw('((result IS NULL ) OR (result =0 ))')
            ->orderBy('isnull', 'asc')
            ->orderBy('high_priority', 'asc')
            ->orderBy('datereceived', 'asc')
            ->orderBy('viralsamples.id', 'asc')
            ->limit(94)
            ->get();

        $count = $samples->count();

        if($count == 94){
            return view('forms.viralworksheets', ['create' => true, 'machine_type' => 2, 'samples' => $samples]);
        }

        return view('forms.viralworksheets', ['create' => false, 'machine_type' => 2, 'count' => $count]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $worksheet = new Viralworksheet;
        $worksheet->fill($request->except('_token'));
        $worksheet->createdby = auth()->user()->id;
        $worksheet->lab_id = auth()->user()->lab_id;
        $worksheet->save();

        $samples = Viralsample::selectRaw("viralsamples.*, viralpatients.patient, view_facilitys.name, viralbatches.datereceived, viralbatches.high_priority, IF(parentid > 0 OR parentid IS NULL, 0, 1) AS isnull")
            ->join('viralbatches', 'viralsamples.batch_id', '=', 'viralbatches.id')
            ->join('viralpatients', 'viralsamples.patient_id', '=', 'viralpatients.id')
            ->leftJoin('view_facilitys', 'view_facilitys.id', '=', 'viralbatches.facility_id')
            ->whereYear('datereceived', '>', 2014)
            ->where('inworksheet', 0)
            ->where('input_complete', true)
            ->whereIn('receivedstatus', [1, 3])
            ->whereRaw('((result IS NULL ) OR (result =0 ))')
            ->orderBy('isnull', 'asc')
            ->orderBy('high_priority', 'asc')
            ->orderBy('datereceived', 'asc')
            ->orderBy('viralsamples.id', 'asc')
            ->limit(94)
            ->get();

        // if($samples->count() != 22 || $samples->count() != 94){
        //     return back();
        // }

        $sample_ids = $samples->pluck('id');

        DB::table('viralsamples')->whereIn('id', $sample_ids)->update(['worksheet_id' => $worksheet->id, 'inworksheet' => true]);

        return redirect()->route('viralworksheet.print', ['worksheet' => $worksheet->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Viralworksheet  $Viralworksheet
     * @return \Illuminate\Http\Response
     */
    public function show(Viralworksheet $Viralworksheet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Viralworksheet  $Viralworksheet
     * @return \Illuminate\Http\Response
     */
    public function edit(Viralworksheet $Viralworksheet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Viralworksheet  $Viralworksheet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Viralworksheet $Viralworksheet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Viralworksheet  $Viralworksheet
     * @return \Illuminate\Http\Response
     */
    public function destroy(Viralworksheet $Viralworksheet)
    {
        //
    }

    public function print(Viralworksheet $worksheet)
    {
        $worksheet->load(['creator']);
        $samples = Sample::where('worksheet_id', $worksheet->id)->with(['patient'])->get();

        if($worksheet->machine_type == 1){
            return view('worksheets.other-table', ['worksheet' => $worksheet, 'samples' => $samples]);
        }
        else{
            return view('worksheets.abbot-table', ['worksheet' => $worksheet, 'samples' => $samples]);
        }
    }

    public function cancel(Viralworksheet $worksheet)
    {
        DB::table("viralsamples")->where('worksheet_id', $worksheet->id)->update(['worksheet_id' => 0, 'inworksheet' => 0, 'result' => 0]);
        $worksheet->status_id = 4;
        $worksheet->datecancelled = date("Y-m-d");
        $worksheet->cancelledby = auth()->user()->id;
        $worksheet->save();

        return redirect("/worksheet");
    }

    public function upload(Viralworksheet $worksheet)
    {
        $worksheet->load(['creator']);
        $users = User::where('user_type_id', '<', 5)->get();
        return view('forms.upload_results', ['worksheet' => $worksheet, 'users' => $users]);
    }





    /**
     * Update the specified resource in storage with results file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Worksheet  $worksheet
     * @return \Illuminate\Http\Response
     */
    public function save_results(Request $request, Worksheet $worksheet)
    {
        $worksheet->fill($request->except(['_token', 'upload']));
        $file = $request->upload->path();
        $today = $dateoftest = date("Y-m-d");
        $positive_control;
        $negative_control;

        if($worksheet->machine_type == 2)
        {
            $dateoftest = $today;
            // config(['excel.import.heading' => false]);
            $data = Excel::load($file, function($reader){
                $reader->toArray();
            })->get();

            $check = array();

            // dd($data);

            $bool = false;
            $positive_control = $negative_control = "Passed";

            foreach ($data as $key => $value) {
                if($value[5] == "RESULT"){
                    $bool = true;
                    continue;
                }

                if($bool){
                    $sample_id = $value[1];
                    $interpretation = $value[5];
                    $error = $value[10];

                    switch ($interpretation) {
                        case 'Not Detected':
                            $result = 1;
                            break;
                        case 'HIV-1 Detected':
                            $result = 2;
                            break;
                        case 'Detected':
                            $result = 2;
                            break;
                        case 'Collect New Sample':
                            $result = 5;
                            break;
                        default:
                            $result = 3;
                            $interpretation = $error;
                            break;
                    }

                    $data_array = ['datemodified' => $today, 'datetested' => $today, 'interpretation' => $interpretation, 'result' => $result];
                    $search = ['id' => $sample_id, 'worksheet_id' => $worksheet->id];
                    DB::table('samples')->where($search)->update($data_array);

                    $check[] = $search;

                    if($sample_id == "HIV_NEG"){
                        $negative_control = $result;
                    }
                    else if($sample_id == "HIV_HIPOS"){
                        $positive_control = $result;
                    }

                }

                if($bool && $value[5] == "RESULT") break;
            }

            if($positive_control == "Passed"){
                $pos_result = 6;
            }
            else{
                $pos_result = 7;
            }

            if($negative_control == "Passed"){
                $neg_result = 6;
            }
            else{
                $neg_result = 7;
            }
        }
        else
        {
            $handle = fopen($file, "r");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)
            {
                $interpretation = $data[8];
                $dateoftest=date("Y-m-d", strtotime($data[3]));

                $flag = $data[10];

                if($flag != NULL){
                    $interpretation = $flag;
                }

                if($interpretation == "Target Not Detected" || $interpretation == "Not Detected DBS")
                {
                    $result = 1;
                } 
                else if($interpretation == 1 || $interpretation == "1" || $interpretation == ">1" || $interpretation == ">1 " || $interpretation == "> 1" || $interpretation == "> 1 " || $interpretation == "1.00E+00" || $interpretation == ">1.00E+00" || $interpretation == ">1.00E+00 " || $interpretation == "> 1.00E+00")
                {
                    $result = 2;
                }
                else
                {
                    $result = 3;
                }

                $data_array = ['datemodified' => $today, 'datetested' => $dateoftest, 'interpretation' => $interpretation, 'result' => $result];

                $search = ['id' => $data[4], 'worksheet_id' => $worksheet->id];
                DB::table('samples')->where($search)->update($data_array);

                if($data[5] == "NC"){
                    // $worksheet->neg_control_interpretation = $interpretation;
                    $negative_control = $result;
                }
                if($data[5] == "LPC" || $data[5] == "PC"){
                    $positive_control = $result;
                }

            }
            fclose($handle);

            switch ($negative_control) {
                case 'Target Not Detected':
                    $neg_result = 1;
                    break;
                case 'Valid':
                    $neg_result = 6;
                    break;
                case 'Invalid':
                    $neg_result = 7;
                    break;
                case '5':
                    $neg_result = 5;
                    break;                
                default:
                    $neg_result = 3;
                    break;
            }

            if($positive_control == 1 || $positive_control == "1" || $positive_control == ">1" || $positive_control == "> 1 " || $positive_control == "> 1" || $positive_control == "1.00E+00" || $positive_control == ">1.00E+00" || $positive_control == "> 1.00E+00" || $positive_control == "> 1.00E+00 ")
            {
                $pos_result = 2;
            }
            else if($positive_control == "5")
            {
                $pos_result = 5;
            }
            else if($positive_control == "Valid")
            {
                $pos_result = 6;
            }
            else if($positive_control == "Invalid")
            {
                $pos_result = 7;
            }
            else
            {
                $pos_result = 3;
            }

        }

        DB::table('samples')->where(['worksheet_id' => $worksheet->id])->where('run', 0)->update(['run' => 1]);

        $worksheet->neg_control_interpretation = $negative_control;
        $worksheet->neg_control_result = $neg_result;

        $worksheet->pos_control_interpretation = $positive_control;
        $worksheet->pos_control_result = $pos_result;
        $worksheet->daterun = $dateoftest;
        $worksheet->save();

        $my = new Misc;
        $my->requeue($worksheet->id);

        // $path = $request->upload->storeAs('eid_results', 'dash.csv');

        return redirect('worksheet/approve/' . $worksheet->id);
    }

    public function approve_results(Viralworksheet $worksheet)
    {
        $worksheet->load(['reviewer', 'creator', 'runner']);

        $results = DB::table('results')->get();
        $actions = DB::table('actions')->get();
        $samples = Viralsample::where('worksheet_id', $worksheet->id)->with(['approver'])->get();

        $s = $this->get_worksheets($worksheet->id);

        $neg = $this->checknull($s->where('result', 1));
        $pos = $this->checknull($s->where('result', 2));
        $failed = $this->checknull($s->where('result', 3));
        $redraw = $this->checknull($s->where('result', 5));
        $noresult = $this->checknull($s->where('result', 0));

        $total = $neg + $pos + $failed + $redraw + $noresult;

        $subtotals = ['neg' => $neg, 'pos' => $pos, 'failed' => $failed, 'redraw' => $redraw, 'noresult' => $noresult, 'total' => $total];

        return view('tables.confirm_results', ['results' => $results, 'actions' => $actions, 'samples' => $samples, 'subtotals' => $subtotals, 'worksheet' => $worksheet]);
    }

    public function approve(Request $request, Worksheet $worksheet)
    {
        $samples = $request->input('samples');
        $batches = $request->input('batches');
        $results = $request->input('results');
        $actions = $request->input('actions');
        // dd($batches);
        $today = date('Y-m-d');
        $approver = auth()->user()->id;

        $batch = array();
        $my = new Misc;

        foreach ($samples as $key => $value) {
            $data = [
                'approvedby' => $approver,
                'dateapproved' => $today,
                'result' => $results[$key],
                'repeatt' => $actions[$key],
            ];

            DB::table('samples')->where('id', $samples[$key])->update($data);

            if($actions[$key] == 1){
                $my->save_repeat($samples[$key]);
            }
        }

        $batch = collect($batches);
        $b = $batch->unique();
        $unique = $b->values()->all();

        foreach ($unique as $value) {
            $my->check_batch($value);
        }

        $worksheet->status_id = 3;
        $worksheet->datereviewed = $today;
        $worksheet->reviewedby = $approver;
        $worksheet->save();
        return redirect('/worksheet');

    }




    public function wstatus()
    {
        $statuses = [
            ['status' => 1, 'string' => "<strong><font color='#FFD324'>In-Process</font></strong>"],
            ['status' => 2, 'string' => "<strong><font color='#0000FF'>Tested</font></strong>"],
            ['status' => 3, 'string' => "<strong><font color='#339900'>Approved</font></strong>"],
            ['status' => 4, 'string' => "<strong><font color='#FF0000'>Cancelled</font></strong>"],
        ];

        return $statuses;
    }

    public function wmachine()
    {
        $machines = [
            collect(['machine' => 1, 'string' => "<strong> TaqMan </strong>"]),
            collect(['machine' => 2, 'string' => "<strong><font color='#0000FF'> Abbott </font></strong>"]),
            collect(['machine' => 3, 'string' => "<strong> C8800 </strong>"]),
            collect(['machine' => 4, 'string' => "<strong><font color='#FF00FB'> Panther </font></strong>"]),
        ];

        return $machines;
    }

    public function get_worksheets($result, $worksheet_id=NULL)
    {
        $samples = Viralsample::selectRaw("count(*) as totals, worksheet_id, result")
            ->whereNotNull('worksheet_id')
            ->when($worksheet_id, function($query) use ($worksheet_id){
                return $query->where('worksheet_id', $worksheet_id);
            })
            ->where('inworksheet', 1)
            ->where('receivedstatus', '!=', 2)
            ->when($result, function($query) use ($result){
                if ($result == 0) {
                    return $query->where('result', '');
                }
                else if ($result == 1) {
                    return $query->where('result', '!=', 'Failed')->where('result', '!=', '< LDL copies/ml');
                }
                else if ($result == 2) {
                    return $query->where('result', '< LDL copies/ml');
                }
                else if ($result == 3) {
                    return $query->where('result', 'Failed');
                }                
            })
            ->groupBy('worksheet_id', 'result')
            ->get();

        return $samples;
    }
}
