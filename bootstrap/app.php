<?php

use App\Http\Middleware\LogRequest;
use App\Http\Middleware\HstsMiddleware;
use App\Http\Middleware\XContentTypeOptionsMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
		api: __DIR__.'/../routes/api.php',  
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
		$middleware->append(LogRequest::class);
		$middleware->append(HstsMiddleware::class);
		$middleware->append(XContentTypeOptionsMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
		$exceptions->renderable(function (\Exception $e, $request) {
			Log::error($e);

			if ($e instanceof \Illuminate\Validation\ValidationException) {
				$status = 422; 
				$errors = $e->errors(); 
	
				if ($request->expectsJson()) {
					return response()->json([
						'message' => 'The given data was invalid.',
						'errors' => $errors
					], $status);
				}
	
				return redirect()->back()
					->withErrors($errors)
					->withInput();
			}

			$status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode() 
                : 500;

			switch ($status) {
				case 404:
					$title = '404 - Not Found';
					$description = 'Sorry, the page you are looking for could not be found.';
					break;
				case 403:
					$title = '403 - Forbidden';
					$description = 'You do not have permission to access this resource.';
					break;
				case 500:
					$title = '500 - Internal Server Error';
					$description = 'Something went wrong on our server. Please try again later.';
					break;
				case 419:
					$title = '419 - Page Expired';
					$description = 'The page expired due to inactivity. Please refresh and try again.';
					break;
				case 401:
					$title = '401 - Unauthorized';
					$description = 'You are not authorized to access this page. Please log in.';
					break;
				default:
					$title = "$status - Unexpected Error";
					$description = 'An unexpected error occurred. Please contact support if this persists.';
					break;
			}

			return response()->view(
                'errors.common',
                [
                    'title' => $title,
                    'description' => $description,
                    'status' => $status
                ],
                $status
            );
		});
    })
	->create();
