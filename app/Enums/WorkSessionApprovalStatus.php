<?php

namespace App\Enums;

enum WorkSessionApprovalStatus: string
{
    case PENDING = 'pending';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function options(): array
    {
        return [
            self::PENDING->value   => 'In attesa',
            self::IN_REVIEW->value => 'Da revisionare',
            self::APPROVED->value  => 'Approvata',
            self::REJECTED->value  => 'Respinta',
        ];
    }
}
