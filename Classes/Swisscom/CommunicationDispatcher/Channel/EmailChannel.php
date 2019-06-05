<?php
namespace Swisscom\CommunicationDispatcher\Channel;

/*
 * This file is part of the Swisscom.CommunicationDispatcher package.
 */

use Swisscom\CommunicationDispatcher\Domain\Model\Dto\Recipient;
use Swisscom\CommunicationDispatcher\Domain\Repository\AssetRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;

/**
 * @Flow\Scope("prototype")
 */
class EmailChannel implements ChannelInterface
{
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var \Neos\SwiftMailer\MailerInterface
     */
    protected $mailer;

    /**
     * @var string
     */
    protected $from;

    /**
     * @var string
     */
    protected $replyTo;

    /**
     * @var string
     */
    protected $cc;

    /**
     * @param array $options
     */
    function __construct(array $options = [])
    {
        $this->from = isset($options['from']) ? $options['from'] : '';
        $this->replyTo = isset($options['replyTo']) ? $options['replyTo'] : '';
        $this->cc = isset($options['cc']) ? $options['cc'] : '';
    }

    /**
     * @param Recipient $recipient
     * @param string $subject
     * @param string $text
     * @param array $attachedResources
     * @return void
     * @throws \Exception
     */
    public function send(Recipient $recipient, $subject, $text, $attachedResources = array())
    {
        $toEmail = $recipient->getEmail();
        $toName = $recipient->getName();

        if (! empty($toEmail)) {
            $mail = new \Swift_Message();
            $mail->setFrom($this->from);
            if (!empty($this->replyTo)) {
                $mail->setReplyTo($this->replyTo);
            }
            $mail->setTo($toEmail, $toName);
            if (!empty($this->cc)) {
                $mail->setCc($this->cc);
            }
            $mail->setSubject(htmlspecialchars_decode($subject));
            $plaintext = preg_replace(array('/\s{2,}/', '/[\t]/', '/###IMAGE:(.+?)###/', '/###PLAIN:(.+?)###/'), ' ', strip_tags($text));
            $text = $this->embedResources($text, $mail);
            $mail->setBody($text, 'text/html', 'utf-8');
            $mail->addPart($plaintext, 'text/plain', 'utf-8');
            /** @var \Neos\Flow\ResourceManagement\PersistentResource $resource */
            foreach ($attachedResources as $resource) {
                $stream = $resource->getStream();
                if ($stream !== false) {
                    /* Resource createTemporaryLocalCopy() does not work as the file needs to be stored until flushing
                    the queue. Create a Swift Attachment to let Swiftmailer take care of it. */
                    $content = fread($stream, $resource->getFileSize());
                    if ($content !== false) {
                        $swiftAttachment = new \Swift_Attachment($content, $resource->getFilename(),
                            $resource->getMediaType());
                        $mail->attach($swiftAttachment);
                    }
                }
            }

            $acceptedRecipients = $this->mailer->send($mail);
            if ($acceptedRecipients <= 0) {
                throw new \Exception();
            }
        }
    }

    /**
     * Embed images. I.e:
     * <img height="40px" src="###IMAGE:'{template.logo}'###" alt="Logo"/>
     *
     * @param $html
     * @param \Swift_Message $mail
     *
     * @return string $html
     */
    private function embedResources($html, &$mail)
    {
        $html = preg_replace_callback('/###IMAGE:(.+?)###/', function ($matches) use ($mail) {
            return $this->embedImageResourceCallback($matches, $mail);
        }, $html);
        $html = preg_replace_callback('/###PLAIN:(.+?)###/', function ($matches) use ($mail) {
            return $this->embedPlainResourceCallback($matches);
        }, $html);

        return $html;
    }

    /**
     * @param array $matches
     * @param \Swift_Message $mail $mail
     *
     * @return string
     */
    private function embedImageResourceCallback($matches, &$mail)
    {
        $cid = '';
        if (isset($matches[1])) {
            $source = trim($matches[1], '\'');
            try {
                $cid = $mail->embed(\Swift_Image::fromPath($source));
            } catch (\Exception $e) {
                // Nothing to do here
            }
        }
        return $cid;
    }

    /**
     * @param array $matches
     *
     * @return string
     */
    private function embedPlainResourceCallback($matches)
    {
        $plain = '';
        if (isset($matches[1])) {
            $source = trim($matches[1], '\'');
            try {
                $plain = file_get_contents($source);
            } catch (\Exception $e) {
                // Nothing to do here
            }
        }
        return $plain;
    }
}
