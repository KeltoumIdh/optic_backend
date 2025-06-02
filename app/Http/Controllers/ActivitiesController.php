<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;


class ActivitiesController extends Controller
{
    /**
     * Get all activities (movements)
     */
    public function getAllActivities(Request $request)
    {

        $all = Activity::with(['User:id,name,role,email'])->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($all ?? [], 200);
    }
}
