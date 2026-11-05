<?php

namespace App\Support\Compliance;

/**
 * The colours an admin can give a choice in Compliance Management → Settings (a status, a severity, …), and the Tailwind classes each one
 * is drawn with. One list, so every page shows the same colour the same way.
 */
class Tones
{
    /** tone => label */
    public const LABELS = ['red' => 'Red', 'amber' => 'Amber', 'violet' => 'Violet', 'blue' => 'Blue', 'green' => 'Green', 'gray' => 'Gray'];

    /** Small pill: tinted background, dark text. */
    public const BADGE = [
        'red' => 'bg-red-100 text-red-700', 'amber' => 'bg-amber-100 text-amber-700', 'violet' => 'bg-violet-100 text-violet-700',
        'blue' => 'bg-blue-100 text-blue-700', 'green' => 'bg-green-100 text-green-700', 'gray' => 'bg-gray-100 text-gray-500',
    ];

    /** Callout card of My Compliance: coloured left border and soft background. */
    public const CARD = [
        'red' => 'bg-red-50 border-red-300', 'amber' => 'bg-amber-50 border-amber-300', 'violet' => 'bg-violet-50 border-violet-300',
        'blue' => 'bg-blue-50 border-blue-300', 'green' => 'bg-green-50 border-green-300', 'gray' => 'bg-gray-50 border-gray-300',
    ];

    public const CARD_TEXT = ['red' => 'text-red-800', 'amber' => 'text-amber-800', 'violet' => 'text-violet-800', 'blue' => 'text-blue-800', 'green' => 'text-green-800', 'gray' => 'text-gray-800'];

    /** The swatch behind the colour picker of the Settings dialogs. */
    public const SWATCH = ['red' => '#dc2626', 'amber' => '#f59e0b', 'violet' => '#7c3aed', 'blue' => '#2563eb', 'green' => '#16a34a', 'gray' => '#6b7280'];

    public static function badge(?string $tone): string
    {
        return self::BADGE[$tone] ?? self::BADGE['gray'];
    }

    public static function valid(): string
    {
        return implode(',', array_keys(self::LABELS));
    }
}
