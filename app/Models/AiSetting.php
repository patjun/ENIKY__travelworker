<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'prompt_de',
        'prompt_en',
        'model',
        'max_tokens',
        'temperature',
    ];

    protected $casts = [
        'temperature' => 'float',
        'max_tokens' => 'integer',
    ];

    /**
     * Get the singleton instance of AI settings.
     * Creates one if it doesn't exist.
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'prompt_de' => static::getDefaultPromptDe(),
                'prompt_en' => static::getDefaultPromptEn(),
                'model' => 'claude-haiku-4-5',
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]
        );
    }

    /**
     * Get the default German prompt.
     */
    public static function getDefaultPromptDe(): string
    {
        return "Du bist ein erfahrener Reise-Content-Writer. Schreibe ein ansprechendes Intro für einen Listicle mit dem Titel '{title}'.\n\n"
            . "Der Listicle enthält folgende Orte: {locations}\n\n"
            . "Das Intro sollte:\n"
            . "- Den Leser neugierig machen\n"
            . "- Die Highlights der genannten Orte anteasern\n"
            . "- Etwa 100-150 Wörter lang sein\n"
            . "- In einem freundlichen, einladenden Ton geschrieben sein\n\n"
            . "Schreibe nur das Intro, ohne zusätzliche Erklärungen.";
    }

    /**
     * Get the default English prompt.
     */
    public static function getDefaultPromptEn(): string
    {
        return "You are an experienced travel content writer. Write an engaging intro for a listicle titled '{title}'.\n\n"
            . "The listicle features the following locations: {locations}\n\n"
            . "The intro should:\n"
            . "- Make the reader curious\n"
            . "- Tease the highlights of the mentioned places\n"
            . "- Be approximately 100-150 words long\n"
            . "- Be written in a friendly, inviting tone\n\n"
            . "Write only the intro, without additional explanations.";
    }
}
