<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\Etudiant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentPublieMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Etudiant $etudiant;
    public Document $document;

    public function __construct(Etudiant $etudiant, Document $document)
    {
        $this->etudiant = $etudiant;
        $this->document = $document;
    }

    public function build()
    {
        $nom = trim(($this->etudiant->prenom ?? '') . ' ' . ($this->etudiant->nom ?? ''));
        return $this->subject('Nouveau document publié: ' . ($this->document->type ?? 'document'))
            ->view('emails.document_publie')
            ->with([
                'nom' => $nom !== '' ? $nom : 'Etudiant',
                'type' => $this->document->type ?? 'document',
                'nom_document' => $this->document->nom ?? 'document.pdf',
                'lien' => url('/'),
            ]);
    }
}
