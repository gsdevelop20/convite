<?php

namespace App\Services;

use App\Support\SendResult;

interface WhatsappGateway
{
    public function sendText(string $phone, string $message, array $options = []): SendResult;

    public function sendButtonList(string $phone, string $message, array $buttons, array $options = []): SendResult;

    public function sendLocation(string $phone, string $title, string $address, string $latitude, string $longitude, array $options = []): SendResult;

    public function sendImage(string $phone, string $imageUrl, ?string $caption = null, array $options = []): SendResult;

    public function sendDocument(string $phone, string $documentUrl, string $extension, string $fileName, ?string $caption = null, array $options = []): SendResult;
}
