<?php

namespace App\Seed;

/**
 * Global supermarket aisles, in shopping order (sortOrder = position).
 * Source of truth for the app:seed-categories command.
 */
final class CategoryCatalog
{
    public const CATEGORIES = [
        'Fruits & Légumes',
        'Boucherie / Volaille',
        'Charcuterie / Traiteur',
        'Poissonnerie',
        'Crèmerie / Produits laitiers',
        'Boulangerie',
        'Épicerie salée',
        'Épicerie sucrée',
        'Condiments & Sauces',
        'Surgelés',
        'Boissons',
        'Hygiène & Beauté',
        'Entretien & Maison',
        'Bébé & Enfant',
        'Animalerie',
    ];
}
