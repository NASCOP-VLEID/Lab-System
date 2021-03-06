<?php

namespace App\Http\Controllers;

use App\DrSample;
use App\Lookup;
use Illuminate\Http\Request;

class DrSampleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Lookup::get_dr();
        $data['dr_samples'] = DrSample::with(['patient.facility'])->paginate();
        return view('tables.dr_samples', $data)->with('pageTitle', 'Drug Resistance Samples');        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = Lookup::get_dr();
        return view('forms.dr_samples', $data)->with('pageTitle', 'Drug Resistance Samples');  
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $drSample = new DrSample;
        if($request->input('submit_type') == 'cancel') return back();
        $viralsamples_arrays = Lookup::viralsamples_arrays();
        $data = $request->only($viralsamples_arrays['dr_sample']);
        $drSample->fill($data);
        $drSample->save();

        session(['toast_message' => 'The sample has been created.']);
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\DrSample  $drSample
     * @return \Illuminate\Http\Response
     */
    public function show(DrSample $drSample)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\DrSample  $drSample
     * @return \Illuminate\Http\Response
     */
    public function edit(DrSample $drSample)
    {
        $drSample->load(['patient.facility']);
        $data = Lookup::get_dr();
        $data['sample'] = $drSample;
        return view('forms.dr_samples', $data)->with('pageTitle', 'Drug Resistance Samples');  
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\DrSample  $drSample
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DrSample $drSample)
    {
        if($request->input('submit_type') == 'cancel') return redirect('/dr_sample');
        $viralsamples_arrays = Lookup::viralsamples_arrays();
        $data = $request->only($viralsamples_arrays['dr_sample']);
        $drSample->fill($data);
        $drSample->save();

        session(['toast_message' => 'The sample has been updated.']);
        return redirect('/dr_sample');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\DrSample  $drSample
     * @return \Illuminate\Http\Response
     */
    public function destroy(DrSample $drSample)
    {
        //
    }
}
