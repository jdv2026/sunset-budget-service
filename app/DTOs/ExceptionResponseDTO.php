<?php

namespace App\DTOs;

use Throwable;

class ExceptionResponseDTO  {

    public function __construct(
        public string $message,
        public int $status,
        public bool $global_error = false,
        public mixed $payload = null,
		public bool $is_show_modal = false,
		public bool $is_custom_message = false,
    ) 
	{
	}

    public static function fromException(Throwable $e, int $status = 500, mixed $payload = null, bool $global_error = false, bool $is_show_modal = false, bool $is_custom_message = false): self {
        return new self(
            message: $e->getMessage(),
            status: $status,
            global_error: $global_error,
            payload: $payload,
			is_show_modal: $is_show_modal,
			is_custom_message: $is_custom_message,
        );
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
