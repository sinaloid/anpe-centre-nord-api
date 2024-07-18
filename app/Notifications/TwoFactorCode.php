<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $data = [
            "code" => $notifiable->two_factor_code
        ];
        return (new MailMessage)
                    ->subject('Code de vérification à deux facteurs')
                    ->line('Votre code de vérification à deux facteurs est : ' . $notifiable->two_factor_code)
                    ->line('Le code expirera dans 10 minutes.')
                    ->line('Si vous n\'avez pas demandé ce code, veuillez ignorer cet email.')
                    ->action('Vérifiez votre compte', 'https://anpebf.com/validation-du-code-a-2-facteurs/'.$notifiable->slug.'/'.$notifiable->two_factor_code);
                    //->action('Vérifiez votre compte', url('/validation-du-code-otp/'.$notifiable->slug.'/'.$notifiable->two_factor_code));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
