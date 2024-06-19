<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $file;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data,$file)
    {
        $this->data = $data;
        $this->file = $file;
    }

    public function build()
    {
        if($this->file){
            return $this->subject($this->data['subject'])
            ->view('mails.verify')
            ->with($this->data)
            ->attach($this->file);
        }else{
            return $this->subject($this->data['subject'])
            ->view('mails.verify')
            ->with($this->data);
        }

    }

    /**
     * Create a new message instance.
     *
     * @return void
     */
}
