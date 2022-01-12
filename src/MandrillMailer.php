<?php
namespace CarloNicora\Minimalism\Service\MandrillMailer;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use CarloNicora\Minimalism\Interfaces\Mailer\Enums\RecipientType;
use CarloNicora\Minimalism\Interfaces\Mailer\Interfaces\EmailInterface;
use CarloNicora\Minimalism\Interfaces\Mailer\Interfaces\MailerInterface;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class MandrillMailer extends AbstractService implements MailerInterface
{
    /** @var string */
    private string $host = 'smtp.mandrillapp.com';

    /** @var int */
    private int $port = 587;

    /**
     * @param string $MINIMALISM_SERVICE_MAILER_MANDRILL_USERNAME
     * @param string $MINIMALISM_SERVICE_MAILER_MANDRILL_PASSWORD
     */
    public function __construct(
        private string $MINIMALISM_SERVICE_MAILER_MANDRILL_USERNAME,
        private string $MINIMALISM_SERVICE_MAILER_MANDRILL_PASSWORD,
    )
    {
    }

    /**
     * @return string|null
     */
    public static function getBaseInterface(
    ): ?string
    {
        return MailerInterface::class;
    }

    /**
     * @param EmailInterface $email
     * @return bool
     */
    public function send(
        EmailInterface $email,
    ): bool
    {
        $mail = new PHPMailer();

        $mail->IsSMTP();

        $mail->Host = $this->host;
        $mail->Port = $this->port;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';

        $mail->Username = $this->MINIMALISM_SERVICE_MAILER_MANDRILL_USERNAME;
        $mail->Password = $this->MINIMALISM_SERVICE_MAILER_MANDRILL_PASSWORD;

        $sender = $email->getSender();
        $mail->From = $sender->getEmailAddress();
        $mail->FromName = $sender->getName()??'';

        $mail->IsHTML(true);
        $mail->Subject = $email->getSubject();
        $mail->Body    = $email->getBody();

        foreach ($email->getRecipients() ?? [] as $recipient) {

            try {
                switch ($recipient->getType()){
                    case RecipientType::To:
                        $mail->addAddress(
                            address: $recipient->getEmailAddress(),
                            name: $recipient->getName()??'',
                        );
                        break;
                    case RecipientType::Cc:
                        $mail->addCC(
                            address: $recipient->getEmailAddress(),
                            name: $recipient->getName()??'',
                        );
                        break;
                    case RecipientType::Bcc:
                        $mail->addBCC(
                            address: $recipient->getEmailAddress(),
                            name: $recipient->getName()??'',
                        );
                        break;
                    default:
                        break;
                }
            } catch (MailerException) {
                throw new RuntimeException('Invalid sender email', 500);
            }
        }

        try {
            if (!$mail->Send()) {
                throw new RuntimeException($mail->ErrorInfo, 500);
            }
        } catch (MailerException) {
            throw new RuntimeException('Error sending the email', 500);
        }

        return true;
    }
}