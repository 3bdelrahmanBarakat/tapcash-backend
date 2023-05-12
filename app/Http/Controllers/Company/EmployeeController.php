<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\AddEmployeesRequest;
use App\Models\Balance;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function addEmployees(AddEmployeesRequest $request)
    {
        $company_id = Auth::user()->id;
        $rule = [
            'employee_id' => [
                'unique:employees,employee_id'
            ]
        ];

        foreach($request['employees'] as $employee)
        {

            $employee_id = User::where('phone_number',$employee['phone_number'])->value('id');

            if(!$employee_id)
            {
                return response()->json([
                    'errors' => "This employee with phone number ".$employee['phone_number']." is not found"
                ], 422);
            }

            if (Employee::where('employee_id',$employee_id)->first()) {
                return response()->json([
                    'errors' => "This employee is already associated"
                ], 422);
            }

            Employee::create([
                'company_id' => $company_id,
                'employee_id' => $employee_id,
                'position' => $employee['position'],
                'salary' => $employee['salary'],
            ]);


        }

        return response()->json(['message' => 'Employees added successfully'], 201);

    }

    public function deleteEmployee(Request $request)
    {
        foreach ($request->employees_id as $employee_id)
        {
           $employee = Employee::where('employee_id', $employee_id)->delete();

           if(!$employee)
           {
            return response()->json([
                'errors' => "This employee is not found"
            ], 422);
           }
        }

     return response()->json(['message' => 'Employee deleted successfully'], 201);
    }

    public function showEmployees()
    {
      $employees=  Employee::where('company_id', Auth::user()->id)->get();

        return response()->json
        ([
            'employees' => $employees,
        ]);
    }

    public function paySalaries(Request $request)
    {
        $company = Auth::user();
        $balance = $company->balance->amount;

        $last_payment = Transaction::where('sender_id', $company->id)->where('type','send')->latest()->first('created_at');

        if ($last_payment && $last_payment->created_at->diffInDays(Carbon::now()) < 30) {
            return response()->json([
                'error' => 'You cannot pay salaries before 30 days have passed since the last payment.'
            ], 400);
        }

        foreach($request['employees_id'] as $employee_id){

            $employee = Employee::where('employee_id', $employee_id)->where('company_id', $company->id)->first();

            if(!$employee)
           {
            return response()->json([
                'errors' => "This employee is not found"
            ], 422);
           }

            $employee_balance = Balance::where('user_id', $employee_id)->first();
          // Check if the company has enough balance to transfer
            if ($balance < $employee->salary) {
                return response()->json(['message' => 'Insufficient balance.'], 400);
            }

        // Deduct the transferred amount from the user's balance
        $balance -= $employee->salary;
        Balance::where('user_id', $company->id)->update(['amount'=> $balance]);

        // Add the transferred amount to the recipient's balance
        $employee_balance->amount += $employee->salary;
        Balance::where('user_id', $employee_id)->update(['amount'=> $employee_balance->amount]);

        Transaction::insert([
            [
            'sender_id' => $company->id,
            'receiver_id' => $employee_id,
            'amount' => $employee->salary,
            'type' => 'send',
            'created_at' =>now()
            ],
        [
            'sender_id' => $company->id,
            'receiver_id' => $employee_id,
            'amount' => $employee->salary,
            'type' => 'receive',
            'created_at' =>now()
        ]
        ]);
    }

        return response()->json([
            'message' => 'Salaries Transfered successfully.',
            'balance' => $balance
        ]);
    }
    }

