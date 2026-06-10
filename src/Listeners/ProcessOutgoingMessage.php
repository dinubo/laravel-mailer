<?php

namespace Dinubo\Mailer\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSending;
use Dinubo\Mailer\Models\Contact;
use Dinubo\Mailer\Models\Message;
use Symfony\Component\Mime\Email;
use Illuminate\Support\Str;
use Dinubo\Mailer\Mailer;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mime\Exception\LogicException;

class ProcessOutgoingMessage
{
    protected Email $mail;

    protected array $data;

    protected ?Message $message = null;

    protected string $type = 'transactional';

    public function handle(MessageSending $event)
    {
        $this->mail = $event->message;
        $this->data = $event->data;

        if (key_exists('mailableType', $this->data) && in_array($this->data['mailableType'], ['communication'])) {
            $this->type = 'communication';
        }

        $this->message();

        $this->addRefId();

        $this->addCategory();

        $this->addReturnPath();

        $this->addListUnsubscribe();

        $this->addClickTracking();

        $this->addUnsubscribeUrl();

        $this->addOpenTracking();

        $this->store();
    }

    protected function refId(): string
    {
        return $this->message()->refId();
    }

    public function generateMessageId(): string
    {
        if ($this->mail->getHeaders()->has('Sender')) {
            $sender = $this->mail->getHeaders()->get('Sender')->getAddress();
        } elseif ($this->mail->getHeaders()->has('From')) {
            $sender = $this->mail->getHeaders()->get('From')->getAddresses()[0];
        } else {
            throw new LogicException('An email must have a "From" or a "Sender" header.');
        }

        return $this->refId() . strstr($sender->getAddress(), '@');
    }

    private function message(): Message
    {
        if ($this->message) {
            return $this->message;
        }

        if ($this->mail->getHeaders()->has('X-Ref-ID')) {
            $refId = $this->mail->getHeaders()->get('X-Ref-ID')->getBodyAsString();

            $message = Message::where('uuid', Mailer::toUuid($refId))->first();

            if ($message) {
                return $this->message = $message;
            }
        }

        $receivable = $this->getReceivable();

        $address = $this->mail->getTo()[0]->getAddress();

        $contact = Contact::from(
            $address,
            $receivable
        );

        $this->message = $contact->messages()->make([]);

        $this->message->setCategory($this->getCategory())
            ->setReceivable($receivable)
            ->setMailable($this->getMailable());

        return $this->message;
    }

    private function store()
    {
        $subject = $this->mail->getSubject();
        $hasAttachments = count($this->mail->getAttachments()) > 0;

        $raw = $hasAttachments ? null : $this->mail->toString();

        $this->message()->fill([
            'subject' => $subject,
            'body' => $raw,
        ])->save();
    }

    protected function getCategory(): ?string
    {
        if ($this->mail->getHeaders()->has('category')) {
            $category = $this->mail->getHeaders()->get('category')->getBodyAsString();

            $this->mail->getHeaders()->remove('category');

            return $category;
        }

        if (key_exists('category', $this->data) && is_string($this->data['category'])) {
            return $this->data['category'];
        }

        return null;
    }

    protected function getMailable(): ?Model
    {
        foreach (config('mailer.mailables') as $key)
        {
            if (key_exists($key, $this->data) && $this->data[$key] instanceof Model) {
                return $this->data[$key];
            }
        }

        return null;
    }

    protected function getReceivable(): ?Model
    {
        foreach (config('mailer.receivables') as $key)
        {
            if (key_exists($key, $this->data) && $this->data[$key] instanceof Model) {
                return $this->data[$key];
            }
        }

        return null;
    }

    private function addRefId()
    {
        if ($this->mail->getHeaders()->has('X-Ref-ID')) {
            $this->mail->getHeaders()->remove('X-Ref-ID');
        }

        $this->mail->getHeaders()->addTextHeader('X-Ref-ID', $this->refId());

        // $this->mail->getHeaders()->addIdHeader('Message-ID', $this->generateMessageId());
        // $this->mail->getHeaders()->addTextHeader('X-PM-KeepID', 'true');
    }

    protected function addCategory()
    {
        if (!$this->message()->category) {
            return;
        }

        $this->mail->getHeaders()->add(new TagHeader($this->message()->category));
    }

    private function addReturnPath()
    {
        if (config('mailer.bounce_address')) {
            $this->mail->getHeaders()->addPathHeader('Return-Path', config('mailer.bounce_address'));
        }
    }

    private function addListUnsubscribe()
    {
        if (config('mailer.unsubscribe_address') && config('mailer.' . $this->type . '.enforce_unsubscriptions')) {
            $this->mail->getHeaders()->addTextHeader('List-Unsubscribe', '<mailto:' . config('mailer.unsubscribe_address') . '?subject=unsubscribe-' . $this->refId() . '>');
        }
    }

    private function addOpenTracking()
    {
        if (!config('mailer.' . $this->type . '.enable_open_tracking')) {
            return;
        }

        $pixel = '<img src="' . route('mailer.open', $this->refId()) . '" style="width:1px;height:1px" alt="" />';

        $body = $this->mail->getHtmlBody();

        if ($body === null) {
            return;
        }

        $body = str_replace('</body>', $pixel . "\n</body>", $body);

        $this->mail->html($body, $this->mail->getHtmlCharset());
    }

    private function addUnsubscribeUrl()
    {
        $url = route('mailer.unsubscribe', $this->refId());

        $this->addTextUnsubscribeUrl($url);

        $body = $this->mail->getHtmlBody();

        if ($body === null) {
            return;
        }

        $element = '<p style="text-align:center;font-size:12px;margin-top:48px;text-decoration:none;color:#aeaeae;">' . "\n"
                . '    <a href="' . $url . '" style="text-decoration:none;color:#aeaeae;">Unsubscribe here</a>' . "\n"
                . '<p>';

        $replaced = 0;

        $body = str_replace('%unsubscribe%', $element, $body, $count);
        $replaced += $count;

        $body = str_replace('%unsubscribe-link%', $url, $body, $count);
        $replaced += $count;

        if ($replaced === 0 && config('mailer.' . $this->type . '.enforce_unsubscriptions')) {
            $body = str_replace('</body>', $element . "\n</body>", $body);
        }


        $this->mail->html($body, $this->mail->getHtmlCharset());
    }

    private function addTextUnsubscribeUrl(string $url)
    {
        $body = $this->mail->getTextBody();

        if ($body === null) {
            return;
        }

        $element = "Unsubscribe here: " . $url;

        $replaced = 0;

        $body = str_replace('%unsubscribe-element%', $element, $body, $count);
        $replaced += $count;

        $body = str_replace('%unsubscribe-link%', $url, $body, $count);
        $replaced += $count;

        if ($replaced === 0 && config('mailer.' . $this->type . '.enforce_unsubscriptions')) {
            $body .= "\n\n" . $element;
        }

        $this->mail->text($body, $this->mail->getTextCharset());
    }

    private function addClickTracking()
    {
        if (!config('mailer.' . $this->type . '.enable_click_tracking')) {
            return;
        }

        try {
            $html = $this->mail->getHtmlBody();

            if ($html === null) {
                return;
            }

            $links = [];

            $dom = new \DOMDocument;

            $dom->loadHTML($html);

            $tags = $dom->getElementsByTagName('a');

            foreach ($tags as $tag) {
                $href = $tag->getAttribute('href');

                if ($href == '%unsubscribe-link%') {
                    continue;
                }

                $key = null;

                if (key_exists($href, $links)) {
                    $key = $links[$href];
                }

                if (!$key) {
                    $key = Str::uuid()->toString();

                    $links[$href] = $key;
                }

                $url = route('mailer.click', ['refId' => $this->refId(), 'key' => Mailer::toPlainId($key)]);

                $tag->setAttribute('href', $url);
            }

            $this->message()->links = array_flip($links);

            $this->mail->html($dom->saveHTML(), $this->mail->getHtmlCharset());

        } catch (\Throwable) {
            // Click tracking is a best-effort enhancement — a parsing failure here
            // must never block the actual send.
        }
    }
}
