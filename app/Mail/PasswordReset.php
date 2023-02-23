<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;
    public $name , $code;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($code,$name)
    {
        $this->name=$name;
        $this->code=$code;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('Doorbell@ioptime.com')
            ->subject('Code Confirmation')
            ->markdown('emails.mail');
    }
}
