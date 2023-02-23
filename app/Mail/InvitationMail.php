<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;
    public $andriod_downlink , $ios_downloadlink , $join_link, $woner_name, $invited_person;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($andriod_downlink,$ios_downloadlink,$join_link,$woner_name,$invited_person)
    {
        $this->andriod_downlink=$andriod_downlink;
        $this->ios_downloadlink=$ios_downloadlink;
        $this->join_link=$join_link;
        $this->woner_name=$woner_name;
        $this->invited_person=$invited_person;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('Doorbell@ioptime.com')
            ->subject('Host Invitation')
            ->markdown('emails.invitation');
    }
}
