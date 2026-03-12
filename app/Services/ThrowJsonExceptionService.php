<?php

namespace App\Services;

use App\DTOs\ExceptionParametersDTO;
use Illuminate\Http\Exceptions\HttpResponseException;

class ThrowJsonExceptionService 
{

	public function throwJsonException(ExceptionParametersDTO $exceptionParameters): never 
	{
		throw new HttpResponseException(response()->json($exceptionParameters->toArray(), $exceptionParameters->status));
	}

}
