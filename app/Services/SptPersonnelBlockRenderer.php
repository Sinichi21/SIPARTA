<?php

namespace App\Services;

use App\Models\Personnel;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/** HTML usable by browser preview and DomPDF (table, inline styles, no flex). */
class SptPersonnelBlockRenderer
{
    /** @param Collection<int,Personnel> $people */
    public function render(Collection $people): HtmlString
    {
        if ($people->isEmpty()) {
            return new HtmlString('');
        }
        $rows = $people->values()->map(function (Personnel $person, int $index): string {
            $grade = trim(implode(' / ', array_filter([$person->rank, $person->grade], fn ($x) => filled($x))));
            $attrs = 'style="padding:2px 4px;vertical-align:top;border:0;"';
            return '<tr><td style="width:25px;padding:2px 4px;vertical-align:top;border:0;">'.($index + 1).'.</td>'
                .'<td '.$attrs.' style="width:126px">Nama / NIP</td><td '.$attrs.'>:</td><td '.$attrs.'>'.e($person->name).'<br>NIP. '.e($person->nip ?: '-').'</td></tr>'
                .'<tr><td></td><td '.$attrs.'>Pangkat Gol./Ruang</td><td '.$attrs.'>:</td><td '.$attrs.'>'.e($grade ?: '-').'</td></tr>'
                .'<tr><td></td><td '.$attrs.'>Jabatan</td><td '.$attrs.'>:</td><td '.$attrs.'>'.e($person->position ?: '-').'</td></tr>';
        })->implode('');
        return new HtmlString('<table style="width:100%;border-collapse:collapse;margin:3mm 0;font-size:11pt">'.$rows.'</table>');
    }
}
