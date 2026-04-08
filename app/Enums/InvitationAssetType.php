<?php

namespace App\Enums;

enum InvitationAssetType: string
{
    case Text = 'text';
    case Image = 'image';
    case Pdf = 'pdf';
}
