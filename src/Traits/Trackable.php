<?php

namespace Dinubo\Mailer\Traits;

use Illuminate\Database\Eloquent\Model;
use Dinubo\Mailer\Models\Contact;
use Dinubo\Mailer\Models\Message;
use Symfony\Component\Mime\Email;

trait Trackable
{
    protected ?Model $receivable = null;
    protected ?Model $mailable = null;
    protected string $category;

    protected function runCallbacks($message)
    {
        $this->withSymfonyMessage(function (Email $mail) {
            $contact = Contact::from(
                $mail->getTo()[0]->getAddress()
            );

            /** @var Message $message */
            $message = $contact->messages()->make([
                'subject' => $this->subject,
                'category' => $this->category,
            ]);

            $message->to($this->receivable)->from($this->mailable)->save();

            $mail->getHeaders()->addTextHeader('X-Ref-ID', $message->uuid);
        });

        return parent::runCallbacks($message);
    }

    // public function to($address, $name = null)
    // {
    //     if ($address instanceof Model) {
    //         $this->receivable = $address;
    //     }

    //     return parent::to($address, $name);
    // }

    public function mailable(Model $mailable)
    {
        $this->mailable = $mailable;

        return $this;
    }

    public function category(string $category)
    {
        $this->category = $category;

        return $this;
    }
}
