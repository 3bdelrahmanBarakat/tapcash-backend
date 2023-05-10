<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\AddEmployeesRequest;
use App\Models\Balance;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
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


        foreach($request['employees_id'] as $employee_id){

            $employee = Employee::where('employee_id', $employee_id)->where('company_id', $company->id)->first();

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
            'type' => 'send'
            ],
        [
            'sender_id' => $company->id,
            'receiver_id' => $employee_id,
            'amount' => $employee->salary,
            'type' => 'receive'
        ]
        ]);
    }

        return response()->json([
            'message' => 'Salaries Transfered successfully.',
            'balance' => $company->balance->amount
        ]);
    }
    }

