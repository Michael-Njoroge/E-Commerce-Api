<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Http\Resources\EnquiryResource;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $enquiries = Enquiry::paginate(20);

        return $this->sendResponse(EnquiryResource::collection($enquiries)
                ->response()
                ->getData(true), "Enquiries retrieved successfully" );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string',
            'comment' => 'required|string',
            'status' => 'nullable|in:Submitted,Contacted,In Progress'
        ]);

        $enquiry = Enquiry::create($data);
        $createdEnquiry = Enquiry::where('id', $enquiry->id)->first();

        return $this->sendResponse(EnquiryResource::make($createdEnquiry)
                ->response()
                ->getData(true), "Enquiry created successfully" );
    }

    /**
     * Display the specified resource.
     */
    public function show(Enquiry $enquiry)
    {
        if($enquiry){
            return $this->sendResponse(EnquiryResource::make($enquiry)
                ->response()
                ->getData(true), "Enquiry retrieved successfully" );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Enquiry $enquiry)
    {
        if($enquiry){
            $enquiry->update($request->all());
            $updatedEnquiry = Enquiry::findOrFail($enquiry->id);

            return $this->sendResponse(EnquiryResource::make($updatedEnquiry)
                ->response()
                ->getData(true), "Enquiry updated successfully" );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Enquiry $enquiry)
    {
        if($enquiry){
            $enquiry->delete();

            return $this->sendResponse([], "Enquiry deleted successfully" );
        }
    }
}

