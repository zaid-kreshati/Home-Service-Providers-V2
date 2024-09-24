<?php

namespace App\Services;

class FirebaseService
{

    protected $serverApiKey;

    public function __construct()
    {
        $this->serverApiKey = env('FIREBASE_SERVER_API_KEY');
    }

    public function sendNotification(array $tokens, string $title, string $body)
    {
        $data = [
            "registration_ids" => $tokens,
            "notification" => [
                "title" => $title,
                "body" => $body,
                "sound" => "default"
            ],
        ];

        $dataString = json_encode($data);
        $headers = [
            'Authorization: key=' . $this->serverApiKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL Error: $error");
        }

        curl_close($ch);
        return json_decode($response, true);
    }
}
