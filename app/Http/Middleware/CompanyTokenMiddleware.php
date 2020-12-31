<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\NotificationHelper;
use App\CompanyToken;

class CompanyTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
		$notif = new NotificationHelper();
		$token = $request->json('token');

		//cek database company token
       	$cek = CompanyToken::where('access_token', $request->json('token'))->count();
       
		if($token == '' || $cek == 0 ) {
			$notif->setUnauthorized();
			
			return response()->json($notif->build());
		}
		
        return $next($request);
    }
}
