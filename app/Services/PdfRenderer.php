<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;

class PdfRenderer
{
    /**
     * Cache simple en mémoire process pour le contenu des modèles.
     * @var array<string,string>
     */
    private static array $templateCache = [];

    public function renderFromHtml(string $html, array $data = []): string
    {
        $htmlFilled = $this->applyVariables($html, $data);
        $pdf = Pdf::loadHTML($htmlFilled)->setPaper('A4');
        return $pdf->output();
    }

    /**
     * Rendre depuis un contenu de modèle déjà chargé (HTML brut)
     */
    public function renderFromTemplateContent(string $templateContent, array $data = []): string
    {
        return $this->renderFromHtml($templateContent, $data);
    }

    public function renderFromTemplatePath(string $templatePath, array $data = []): string
    {
        $ext = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['docx', 'odt'])) {
            return $this->renderFromWordTemplate($templatePath, $data);
        }

        $html = $this->readTemplate($templatePath);
        return $this->renderFromHtml($html, $data);
    }

    private function readTemplate(string $path): string
    {
        // Cache clé: chemin résolu + mtime
        $resolved = $this->resolvePath($path);
        $cacheKey = $resolved.'#'.(@filemtime($resolved) ?: '0');

        if (isset(self::$templateCache[$cacheKey])) {
            return self::$templateCache[$cacheKey];
        }

        $content = @file_get_contents($resolved);
        if ($content === false) {
            throw new \RuntimeException('Modèle introuvable: '.$path);
        }

        // Mettre en cache
        self::$templateCache[$cacheKey] = $content;
        return $content;
    }

    private function applyVariables(string $html, array $data): string
    {
        $flat = $this->flatten($data);
        foreach ($flat as $key => $value) {
            $placeholder = '{{ '.str_replace(['\\', '"'], ['\\\\', '"'], $key).' }}';
            $html = str_replace($placeholder, (string) $value, $html);
            // Also support placeholders without spaces: {{key}}
            $html = str_replace('{{'.$key.'}}', (string) $value, $html);
        }
        return $html;
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix.'.'.$key;
            if (is_array($value)) {
                $result += $this->flatten($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    private function renderFromWordTemplate(string $templatePath, array $data): string
    {
        // Résoudre chemin
        $resolved = $this->resolvePath($templatePath);

        // 1) Remplacer variables avec TemplateProcessor
        $tmpDocx = tempnam(sys_get_temp_dir(), 'tpl_');
        @unlink($tmpDocx);
        $tmpDocx .= '.docx';

        // Si ODT, on le copie avec extension docx pour le writer après remplacement
        $sourcePath = $resolved;
        if (strtolower(pathinfo($resolved, PATHINFO_EXTENSION)) === 'odt') {
            // TemplateProcessor supporte ODT depuis PhpWord 1.0+ (limité). On tente directement.
        }

        $processor = new TemplateProcessor($sourcePath);

        // Aplatir clés en format TemplateProcessor: etudiant_nom => ${etudiant_nom}
        $flat = $this->flattenForWord($data);

        // Détecter tableaux (array de lignes): ex: matieres => [ {nom:..}, {nom:..} ]
        foreach ($data as $k => $v) {
            if (is_array($v) && !empty($v) && isset($v[0]) && is_array($v[0])) {
                // Chercher une colonne existante pour cloneRow, on prend la première clé
                $firstRow = $v[0];
                $firstKey = array_key_first($firstRow);
                if ($firstKey !== null) {
                    $base = $k.'_'.$firstKey; // e.g. matieres_nom
                    try {
                        $processor->cloneRow($base, count($v));
                        $i = 1;
                        foreach ($v as $row) {
                            foreach ($row as $col => $val) {
                                $processor->setValue($k.'_'.$col.'#'.$i, (string)$val);
                            }
                            $i++;
                        }
                    } catch (\Throwable $e) {
                        // Si cloneRow échoue (pas de placeholder), on ignore silencieusement
                    }
                }
            }
        }

        // Remplacement des scalaires
        foreach ($flat as $key => $val) {
            try {
                $processor->setValue($key, (string)$val);
            } catch (\Throwable $e) {
                // ignorer valeurs non présentes
            }
        }

        $processor->saveAs($tmpDocx);

        // 2) Charger le DOCX généré et convertir en HTML
        $phpWord = IOFactory::load($tmpDocx);
        $tmpHtml = tempnam(sys_get_temp_dir(), 'html_').'.html';
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        $htmlWriter->save($tmpHtml);
        $html = @file_get_contents($tmpHtml) ?: '';

        // 3) Convertir HTML en PDF en appliquant aussi les variables (permet support {{ ... }} dans DOCX)
        return $this->renderFromHtml($html, $data);
    }

    private function resolvePath(string $path): string
    {
        $candidates = [
            $path,
            base_path($path),
            resource_path(trim($path, '/')),
            resource_path('views/'.trim($path, '/')),
            storage_path('app/'.trim($path, '/')),
            storage_path('app/public/'.trim($path, '/')),
        ];
        foreach ($candidates as $c) {
            if (@is_file($c)) return $c;
        }
        throw new \RuntimeException('Modèle introuvable: '.$path);
    }

    private function flattenForWord(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix.'_'.$key;
            if (is_array($value)) {
                // On ne descend pas dans les tableaux de lignes (gérés par cloneRow)
                $isList = isset($value[0]) && is_array($value[0]);
                if ($isList) {
                    continue;
                }
                $result += $this->flattenForWord($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
