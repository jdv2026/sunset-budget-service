<?php

namespace App\DTOs;

class ExceptionParametersDTO  {

    public function __construct(
		public string $message,
		public int $status,
		public mixed $payload = null,
		public bool $global_error = false,
		public bool $is_show_modal = false,
		public bool $is_custom_message = false,
    ) 
	{
	}

    public function toArray(): array {
        return [
            'message' => $this->message,
            'status' => $this->status,
            'global_error' => $this->global_error,
            'payload' => $this->payload,
			'is_show_modal' => $this->is_show_modal,
			'is_custom_message' => $this->is_custom_message,
        ];
    }
}
