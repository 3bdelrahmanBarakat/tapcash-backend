<?php

namespace App\Http\Middleware\Company;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(User::find(Auth::user()->id)->type != "company")
        {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return $next($request);
    }
}
