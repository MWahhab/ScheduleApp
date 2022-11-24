<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        //by default, this function returns the current week
        //the function returns an array with all carbon day objects
        //between the two dates
        $days = $this->getAllDaysBetweenTwoDates();

        //gets all tasks from the carbon days passed in array param
        $tasks = $this->getTasksForDaysOfWeek();

        return view('task.list', compact('days', 'tasks'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {

        $request->validate(
            [
                'description' => ['required', 'string', 'max:1000']
            ]);

        $insertTask = DB::table('tasks')->insert([
            'description' => $request->input('description'),
            'repeating' => $request->input('repeating') ?? "off",
            'monday' => $request->input('monday') ?? "off",
            'tuesday' => $request->input('tuesday') ?? "off",
            'wednesday' => $request->input('wednesday') ?? "off",
            'thursday' => $request->input('thursday') ?? "off",
            'friday' => $request->input('friday') ?? "off",
            'saturday' => $request->input('saturday') ?? "off",
            'sunday' => $request->input('sunday') ?? "off",
            'time_due' => $request->input('timepicker') ?? null,
            'date_due' => $request->input('datepicker_create') ?? null,
            'created_at' => now()
        ]);

        if ($insertTask) {
            return redirect()->back()->with('success', 'task created successfully!');
        } else {
            return redirect()->back()->with('error', 'something went wrong!');
        }
    }

    /**
     * Display the specified resource.
     * This function ends up being called by Ajax from the edit view
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request): \Illuminate\Http\JsonResponse
    {
        $editFormData = $this->getTaskById($request->id);
        if ($editFormData) {
            return response()->json([
                'status' => 200,
                'task' => $editFormData
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Task not found!',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Task $task
     * @return RedirectResponse
     */
    public function edit(Request $request): RedirectResponse
    {
        $request->validate(
            [
                'description' => ['required', 'string', 'max:1000']
            ]);

        Task::where('id', $request->id)
            ->update([
                'description' => $request->input('description'),
                'repeating' => $request->input('repeating'),
                'tuesday' => $request->input('tuesday'),
                'wednesday' => $request->input('wednesday'),
                'thursday' => $request->input('thursday'),
                'friday' => $request->input('friday'),
                'saturday' => $request->input('saturday'),
                'sunday' => $request->input('sunday'),
                'time_due' => $request->input('timepicker_edit'),
                'date_due' => $request->input('datepicker_edit'),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'task created successfully!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Task $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Task $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        //
    }

    /**
     * Returns all days between two specific dates.
     * If parameters are not set, it returns all days in the current week
     * @param string $startDate
     * @param string $endDate
     * @return array{}
     */
    public function getAllDaysBetweenTwoDates(string $startDate = "", string $endDate = ""): array
    {

        $now = Carbon::now();

        if (!$startDate) {
            $startDate = $now->startOfWeek()->format('d-m-Y H:i');
        }

        if (!$endDate) {
            $endDate = $now->endOfWeek()->format('d-m-Y H:i');
        }

        return CarbonPeriod::create($startDate, $endDate)->toArray();
    }

    /**
     * Returns all tasks on the given days.
     * Given days are an array of Carbon dates
     * If parameter is not set, it returns all tasks.
     * @param array $daysOfWeek
     * @return array{}
     */
    public function getTasksForDaysOfWeek(array $daysOfWeek = array()): array
    {
        if ($daysOfWeek) {
            $result = array();

            //$daysOfWeek is an array with carbon days inside
            foreach ($daysOfWeek as $day) {

                $tasks = DB::table('tasks')

                    // tasks which are repeating are not date sensitive
                    // we are fetching all tasks which are repeating on the
                    // specific day of the week
                    ->where(strtolower($day->dayName), ['on'])
                    ->where('repeating', ['on'])

                    // tasks which are not repeating are date sensitive
                    // these need to match the day AND the date
                    ->orWhere(strtolower($day->dayName), ['on'])
                    ->where('date_due', [$day->isoFormat('DD/MM/YYYY')])
                    ->get()
                    ->toArray();

                if ($tasks) {
                    $result = $tasks;
                }

            }
        } else {
            //if there are no days passed, by default we fetch all tasks
            $tasks = DB::table('tasks')->get()->toArray();
            $result = $tasks;
        }

        return $result;
    }

    /**
     * Returns a task by Id if it exists
     * If it doesn't exist, it returns empty array
     * @param $taskId
     * @return array
     */
    public function getTaskById(int $taskId): array
    {

        $task = Task::where('id', $taskId)->firstOrFail()->toArray();

        return $task;
    }
}
