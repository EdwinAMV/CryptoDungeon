<?php

namespace App\Services;

use App\Enums\CipherType;
use App\Models\Room;

// Private teacher material: call only after authorizing the room owner.
class PuzzleSolutionGuide
{
    public function describe(Room $room): string
    {
        return match ($room->cipher_type) {
            CipherType::Caesar => $this->caesar($room),
            CipherType::SimpleSubstitution => $this->substitution($room),
            CipherType::Vigenere => $this->vigenere($room),
        };
    }

    private function caesar(Room $room): string
    {
        $shift = (int) $room->public_parameters['shift'];
        $cipherLetters = preg_replace('/[^A-Z]/', '', $room->ciphertext);
        $examples = [];
        foreach (str_split(substr($cipherLetters, 0, 6)) as $letter) {
            $value = ord($letter) - 65;
            $plain = ($value - $shift + 26) % 26;
            $examples[] = "  {$letter} ({$value}) - {$shift} => {$plain} (".chr(65 + $plain).')';
        }

        return "Parámetro: desplazamiento = {$shift}\n"
            ."Procedimiento de descifrado:\n"
            ."1. Asigna A=0, B=1, ..., Z=25.\n"
            ."2. Retrocede {$shift} posiciones por cada letra cifrada.\n"
            ."3. Si pasas de A, continúa desde Z. Conserva los espacios.\n"
            ."Fórmula: claro = (cifrado - desplazamiento + 26) mod 26.\n"
            ."Ejemplo con las primeras letras (resultado módulo 26):\n".implode("\n", $examples)."\n";
    }

    private function substitution(Room $room): string
    {
        $alphabet = $room->public_parameters['alphabet'];
        $cipherLetters = preg_replace('/[^A-Z]/', '', $room->ciphertext);
        $examples = [];
        foreach (str_split(substr($cipherLetters, 0, 6)) as $letter) {
            $examples[] = '  '.$letter.' => '.chr(65 + strpos($alphabet, $letter));
        }

        return "Parámetros de sustitución:\n"
            ."  Original: ABCDEFGHIJKLMNOPQRSTUVWXYZ\n"
            ."  Cifrado:  {$alphabet}\n"
            ."Procedimiento de descifrado:\n"
            ."1. Busca cada letra del mensaje en la fila Cifrado.\n"
            ."2. Sustitúyela por la letra de la misma columna en Original.\n"
            ."3. Repite hasta completar el mensaje, conservando los espacios.\n"
            ."Atención: se lee de Cifrado a Original, en sentido inverso al cifrado.\n"
            ."Ejemplo con las primeras letras:\n".implode("\n", $examples)."\n";
    }

    private function vigenere(Room $room): string
    {
        $key = $room->public_parameters['key'];
        $position = 0;
        $alignedKey = '';
        $decoded = '';
        $examples = [];
        foreach (str_split($room->ciphertext) as $letter) {
            if ($letter < 'A' || $letter > 'Z') {
                $alignedKey .= ' ';
                $decoded .= $letter;

                continue;
            }
            $keyLetter = $key[$position++ % strlen($key)];
            $value = ord($letter) - 65;
            $shift = ord($keyLetter) - 65;
            $plain = ($value - $shift + 26) % 26;
            $alignedKey .= $keyLetter;
            $decoded .= chr(65 + $plain);
            if (count($examples) < 6) {
                $examples[] = "  {$letter} ({$value}) - {$keyLetter} ({$shift}) => {$plain} (".chr(65 + $plain).')';
            }
        }

        return "Parámetro: clave = {$key}\n"
            ."Procedimiento de descifrado:\n"
            ."1. Repite la clave sobre las letras del mensaje. Los espacios no consumen clave.\n"
            ."2. Asigna A=0, B=1, ..., Z=25 a mensaje y clave.\n"
            ."3. Resta el valor de la clave al de la letra cifrada; si es negativo, suma 26.\n"
            ."4. Convierte el resultado a letra y conserva los espacios.\n"
            ."Fórmula: claro = (cifrado - clave + 26) mod 26.\n"
            ."Alineación completa de esta habitación:\n"
            ."  Cifrado: {$room->ciphertext}\n  Clave:   {$alignedKey}\n  Claro:   {$decoded}\n"
            ."Ejemplo con las primeras letras (resultado módulo 26):\n".implode("\n", $examples)."\n";
    }
}
