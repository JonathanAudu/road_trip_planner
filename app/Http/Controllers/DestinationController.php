<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index()
    {
        // Fetch all destinations from the database
        $destinations = Destination::all();

        // Return the view with the destinations data
        return view('destinations.index', compact('destinations'));
    }
}
