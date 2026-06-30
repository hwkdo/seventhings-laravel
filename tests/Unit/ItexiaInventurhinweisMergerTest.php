<?php

declare(strict_types=1);

use Hwkdo\SeventhingsLaravel\Support\ItexiaInventurhinweisMerger;

it('ersetzt leeren inventurhinweis', function (): void {
    expect(ItexiaInventurhinweisMerger::merge(null, 'Neu', true))->toBe('Neu')
        ->and(ItexiaInventurhinweisMerger::merge('  ', 'Neu', true))->toBe('Neu');
});

it('haengt inventurhinweis an bestehenden text an', function (): void {
    $merged = ItexiaInventurhinweisMerger::merge('Alt', 'Neu', true);

    expect($merged)->toBe("Alt\n\nNeu");
});

it('ueberschreibt inventurhinweis wenn anhaengen deaktiviert', function (): void {
    expect(ItexiaInventurhinweisMerger::merge('Alt', 'Neu', false))->toBe('Neu');
});
