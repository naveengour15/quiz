<?php
 
namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
 
    public $data;
    public $subjectText;
 
    public function __construct($data, $subjectText='Welcome to Chalo Online')
    {
        $this->data = $data;
        $this->subjectText = $subjectText;
    }
 
    // public function build()
    // {
    //     return $this->subject($this->subjectText)
    //         ->view('emails.common')
    //         ->with([
    //             'data' => $this->data,
    //         ]);
    // }
 
    public function build()
    {
        return $this->subject($this->subjectText)
            ->html(
                '<p style="font-size:16px; color:#333;">
                    Hello '.$this->data['name'].'
                </p>'.$this->data['message']
            );
    }
 
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectText,
        );
    }
 
    public function content(): Content
    {
        return new Content(
            view: 'website.emails.test', // ✅ correct
        );
    }
   
 
    public function attachments(): array
    {
        return [];
    }
}