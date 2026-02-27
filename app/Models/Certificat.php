<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificat extends Model
{
    protected $primaryKey = 'id_certificat';

    protected $fillable = [
        'id_etudiant',
        'filiere',
        'niveau_valide',
        'niveau_suivant',
        'annee_academique',
        'cours_valides',
        'moyenne_generale',
        'mention',
        'code_verification',
        'nom_responsable',
        'titre_responsable',
        'signature_base64',
        'est_signe',
    ];

    protected $casts = [
        'cours_valides'   => 'array',
        'moyenne_generale'=> 'float',
        'est_signe'       => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'id_etudiant');
    }

    // ── Accesseur mention automatique ──────────────────────────
    public static function calculerMention(float $moyenne): string
    {
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Insuffisant';
    }

    // ── Générer code unique ────────────────────────────────────
    public static function genererCode(string $filiere, string $niveau): string
    {
        $prefix = strtoupper(substr($filiere, 0, 3));
        $annee  = date('Y');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));
        return "CERT-{$prefix}-{$niveau}-{$annee}-{$random}";
    }

    // ── Niveau suivant automatique ─────────────────────────────
    public static function niveauSuivant(string $niveau): string
    {
        $map = [
            'L1' => 'L2', 'L2' => 'L3', 'L3' => 'M1',
            'M1' => 'M2', 'M2' => 'M3', 'M3' => 'D1',
            'D1' => 'D2', 'D2' => 'D3', 'D3' => 'Diplômé',
            'S1' => 'S2', 'S2' => 'S3', 'S3' => 'S4',
            'S4' => 'S5', 'S5' => 'S6', 'S6' => 'Diplômé',
        ];
        return $map[$niveau] ?? 'Niveau supérieur';
    }
}