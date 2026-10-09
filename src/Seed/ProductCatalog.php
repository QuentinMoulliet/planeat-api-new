<?php

namespace App\Seed;

/**
 * Default food product catalogue, copied into each new household (then freely editable).
 * Keys must match CategoryCatalog names. Products listed in STAPLES are flagged "toujours en stock".
 */
final class ProductCatalog
{
    public const STAPLES = [
        'Sel fin',
        'Gros sel',
        'Fleur de sel',
        'Poivre noir',
        'Poivre blanc',
        'Huile d\'olive',
        'Huile de tournesol',
        'Huile de colza',
        'Vinaigre de vin',
        'Vinaigre balsamique',
        'Herbes de Provence',
    ];

    public const PRODUCTS = [
        'Fruits & Légumes' => [
            // Légumes
            'Ail', 'Artichaut', 'Asperge blanche', 'Asperge verte', 'Aubergine', 'Avocat', 'Betterave cuite',
            'Betterave crue', 'Blette', 'Brocoli', 'Butternut', 'Carotte', 'Céleri branche', 'Céleri-rave',
            'Champignons de Paris', 'Champignons shiitake', 'Pleurotes', 'Cèpes', 'Girolles', 'Chou blanc',
            'Chou chinois', 'Choux de Bruxelles', 'Chou kale', 'Chou-fleur', 'Chou rouge', 'Chou vert',
            'Chou romanesco', 'Concombre', 'Courge', 'Courgette', 'Cresson', 'Échalote', 'Endive',
            'Épinards frais', 'Fenouil', 'Fèves', 'Haricots verts', 'Haricots plats', 'Mâche', 'Laitue',
            'Batavia', 'Feuille de chêne', 'Roquette', 'Jeunes pousses', 'Mesclun', 'Salade iceberg',
            'Romaine', 'Frisée', 'Scarole', 'Épi de maïs', 'Navet', 'Oignon jaune', 'Oignon rouge',
            'Oignon blanc', 'Oignon nouveau', 'Panais', 'Patate douce', 'Petits pois frais', 'Piment frais',
            'Poireau', 'Poivron rouge', 'Poivron vert', 'Poivron jaune', 'Pomme de terre',
            'Pommes de terre grenailles', 'Potimarron', 'Potiron', 'Radis', 'Radis noir', 'Rhubarbe',
            'Rutabaga', 'Salsifis', 'Tomate', 'Tomates cerises', 'Tomate cœur de bœuf', 'Tomates grappe',
            'Topinambour', 'Gingembre frais', 'Citronnelle', 'Pak choï', 'Pousses de soja', 'Chou-rave',
            'Cornichons frais', 'Mini-carottes',
            // Herbes fraîches
            'Basilic', 'Persil plat', 'Persil frisé', 'Ciboulette', 'Coriandre', 'Menthe', 'Aneth',
            'Estragon', 'Thym frais', 'Romarin frais', 'Cerfeuil', 'Sauge', 'Laurier frais',
            // Fruits
            'Abricot', 'Ananas', 'Banane', 'Cassis', 'Cerise', 'Châtaigne', 'Citron', 'Citron vert',
            'Clémentine', 'Mandarine', 'Coing', 'Figue', 'Fraise', 'Framboise', 'Fruit de la passion',
            'Grenade', 'Groseille', 'Kaki', 'Kiwi', 'Litchi', 'Mangue', 'Melon', 'Mirabelle', 'Mûre',
            'Myrtille', 'Nectarine', 'Orange', 'Orange à jus', 'Pamplemousse', 'Papaye', 'Pastèque',
            'Pêche', 'Poire', 'Pomme', 'Prune', 'Quetsche', 'Raisin blanc', 'Raisin noir', 'Reine-claude',
            'Noix de coco', 'Physalis', 'Brugnon', 'Kumquat', 'Fruits rouges',
        ],
        'Boucherie / Volaille' => [
            // Bœuf
            'Viande hachée', 'Steak haché', 'Steak', 'Entrecôte', 'Côte de bœuf', 'Faux-filet', 'Rumsteck',
            'Bavette', 'Onglet', 'Filet de bœuf', 'Rôti de bœuf', 'Bœuf à braiser', 'Bœuf pour pot-au-feu',
            'Joue de bœuf', 'Paleron', 'Plat de côtes', 'Carpaccio de bœuf', 'Bœuf haché pour tartare',
            'Émincé de bœuf', 'Brochettes de bœuf',
            // Veau
            'Escalope de veau', 'Veau pour blanquette', 'Rôti de veau', 'Côte de veau', 'Osso buco',
            'Paupiette de veau', 'Foie de veau',
            // Porc
            'Côte de porc', 'Échine de porc', 'Filet mignon de porc', 'Rôti de porc', 'Travers de porc',
            'Poitrine de porc', 'Sauté de porc', 'Chair à saucisse', 'Saucisse de Toulouse', 'Chipolatas',
            'Merguez', 'Saucisse de Montbéliard', 'Saucisse de Morteau', 'Andouillette', 'Boudin noir',
            'Boudin blanc', 'Jarret de porc', 'Palette de porc', 'Saucisses fumées',
            // Agneau
            'Gigot d\'agneau', 'Côtelettes d\'agneau', 'Épaule d\'agneau', 'Souris d\'agneau',
            'Agneau pour navarin',
            // Volaille & autres
            'Poulet entier', 'Blanc de poulet', 'Cuisse de poulet', 'Pilons de poulet', 'Ailes de poulet',
            'Hauts de cuisse de poulet', 'Émincé de poulet', 'Escalope de dinde', 'Rôti de dinde',
            'Émincé de dinde', 'Magret de canard', 'Cuisse de canard', 'Aiguillettes de canard', 'Pintade',
            'Caille', 'Lapin', 'Cordon bleu', 'Foies de volaille', 'Brochettes de poulet', 'Coquelet',
        ],
        'Charcuterie / Traiteur' => [
            // Charcuterie
            'Jambon blanc', 'Jambon cru', 'Bacon', 'Lardons nature', 'Lardons fumés', 'Allumettes de jambon',
            'Chorizo', 'Saucisson sec', 'Salami', 'Coppa', 'Pancetta', 'Rosette', 'Pâté', 'Pâté en croûte',
            'Rillettes', 'Terrine', 'Foie gras', 'Saucisses de Strasbourg', 'Blanc de dinde', 'Blanc de poulet en tranches',
            'Jambon à l\'os', 'Viande des Grisons', 'Mortadelle', 'Speck', 'Gésiers confits',
            // Pâtes à étaler
            'Pâte brisée', 'Pâte feuilletée', 'Pâte sablée', 'Pâte à pizza', 'Pâte à crêpes', 'Pâte filo',
            // Traiteur
            'Tagliatelles fraîches', 'Ravioli frais', 'Tortellini', 'Gnocchi', 'Ravioles du Dauphiné',
            'Houmous', 'Tzatziki', 'Taboulé', 'Guacamole', 'Quenelles', 'Crêpes', 'Galettes de sarrasin',
            'Tofu', 'Tofu fumé', 'Steak végétal', 'Falafels', 'Choucroute', 'Salade de carottes râpées',
            'Céleri rémoulade', 'Piémontaise', 'Nems', 'Samoussas', 'Wraps garnis', 'Quiche',
        ],
        'Poissonnerie' => [
            'Pavé de saumon', 'Saumon fumé', 'Filet de cabillaud', 'Dos de cabillaud', 'Lieu noir', 'Merlu',
            'Colin', 'Sole', 'Bar', 'Dorade', 'Truite', 'Truite fumée', 'Thon frais', 'Maquereau',
            'Sardines fraîches', 'Lotte', 'Merlan', 'Églefin', 'Haddock', 'Raie', 'Crevettes cuites',
            'Crevettes crues', 'Gambas', 'Moules', 'Noix de Saint-Jacques', 'Huîtres', 'Calamars', 'Poulpe',
            'Seiche', 'Tarama', 'Surimi', 'Filet de julienne', 'Rouget', 'Saumon entier', 'Palourdes',
            'Bulots', 'Langoustines', 'Tourteau', 'Brandade de morue', 'Morue salée', 'Anchois frais',
            'Œufs de saumon', 'Rillettes de saumon',
        ],
        'Crèmerie / Produits laitiers' => [
            // Lait, beurre, crème, œufs
            'Lait demi-écrémé', 'Lait entier', 'Lait écrémé', 'Lait sans lactose', 'Lait d\'avoine',
            'Lait d\'amande', 'Lait de soja', 'Beurre doux', 'Beurre demi-sel', 'Margarine',
            'Crème fraîche épaisse', 'Crème fraîche liquide', 'Crème légère', 'Crème liquide entière',
            'Crème chantilly', 'Crème de soja', 'Œufs', 'Lait fermenté', 'Lait ribot',
            // Yaourts & desserts
            'Yaourt nature', 'Yaourts aux fruits', 'Yaourt grec', 'Fromage blanc', 'Petits suisses', 'Skyr',
            'Faisselle', 'Crème dessert', 'Mousse au chocolat', 'Riz au lait', 'Flan', 'Compote en gourde',
            'Yaourts à boire', 'Panna cotta',
            // Fromages
            'Emmental râpé', 'Gruyère râpé', 'Parmesan', 'Mozzarella', 'Mozzarella râpée', 'Burrata',
            'Comté', 'Emmental', 'Gruyère', 'Fromage à raclette', 'Reblochon', 'Camembert', 'Brie',
            'Roquefort', 'Bleu d\'Auvergne', 'Gorgonzola', 'Chèvre frais', 'Bûche de chèvre', 'Crottin de chèvre',
            'Feta', 'Ricotta', 'Mascarpone', 'Mont d\'Or', 'Cheddar', 'Tomme', 'Fromage frais à tartiner',
            'Fromage ail et fines herbes', 'Mimolette', 'Munster', 'Maroilles', 'Morbier', 'Beaufort',
            'Halloumi', 'Pecorino', 'Fromage à fondue', 'Saint-Nectaire', 'Cantal', 'Coulommiers',
            'Fourme d\'Ambert', 'Raclette fumée', 'Abondance', 'Ossau-Iraty', 'Fromage fondu',
            'Fromage à tartiflette', 'Cancoillotte', 'Boursin cuisine', 'Saint-Marcellin', 'Époisses',
            'Bleu', 'Edam', 'Gouda',
        ],
        'Boulangerie' => [
            'Baguette', 'Pain de campagne', 'Pain complet', 'Pain aux céréales', 'Pain de seigle',
            'Pain de mie', 'Pain de mie complet', 'Pains burger', 'Pains hot-dog', 'Pain pita', 'Pain naan',
            'Ciabatta', 'Focaccia', 'Croissants', 'Pains au chocolat', 'Pains aux raisins', 'Brioche',
            'Pains au lait', 'Biscottes', 'Pain grillé', 'Muffins anglais', 'Bagels', 'Pain pour croque-monsieur',
            'Fougasse', 'Pain sans gluten', 'Tortillas de blé', 'Tortillas de maïs',
        ],
        'Épicerie salée' => [
            // Pâtes
            'Pâtes', 'Spaghetti', 'Tagliatelles', 'Penne', 'Fusilli', 'Coquillettes', 'Macaroni', 'Farfalle',
            'Linguine', 'Rigatoni', 'Conchiglie', 'Feuilles de lasagnes', 'Cannelloni', 'Vermicelles',
            'Pâtes à soupe', 'Orzo', 'Nouilles chinoises', 'Nouilles de riz', 'Nouilles udon',
            'Nouilles instantanées', 'Pâtes complètes',
            // Riz, céréales, légumineuses
            'Riz', 'Riz basmati', 'Riz thaï', 'Riz long', 'Riz arborio', 'Riz complet', 'Riz à sushi',
            'Riz sauvage', 'Semoule', 'Boulgour', 'Quinoa', 'Blé précuit', 'Polenta', 'Lentilles vertes',
            'Lentilles corail', 'Lentilles beluga', 'Pois chiches', 'Haricots rouges', 'Haricots blancs',
            'Haricots noirs', 'Flageolets', 'Pois cassés', 'Purée en flocons', 'Fécule de maïs',
            'Chapelure', 'Croûtons', 'Farine de sarrasin', 'Farine de maïs', 'Graines de chia',
            'Graines de lin', 'Graines de courge', 'Graines de tournesol', 'Graines de sésame',
            // Conserves
            'Tomates pelées', 'Tomates concassées', 'Coulis de tomate', 'Concentré de tomate', 'Sauce tomate',
            'Maïs en conserve', 'Petits pois en conserve', 'Petits pois carottes', 'Haricots verts en conserve',
            'Champignons en conserve', 'Thon en conserve', 'Sardines en conserve', 'Maquereaux en conserve',
            'Anchois', 'Miettes de crabe', 'Lait de coco', 'Crème de coco', 'Olives vertes', 'Olives noires',
            'Tomates séchées', 'Poivrons grillés', 'Cœurs d\'artichaut', 'Cœurs de palmier', 'Cassoulet',
            'Choucroute en conserve', 'Ratatouille en conserve', 'Lentilles cuisinées', 'Raviolis en conserve',
            'Pois chiches en conserve', 'Haricots rouges en conserve', 'Confit de canard', 'Soupe en brique',
            'Velouté', 'Gaspacho', 'Sauce bolognaise', 'Sauce carbonara', 'Sauce pesto',
            // Bouillons
            'Bouillon cube de volaille', 'Bouillon cube de légumes', 'Bouillon cube de bœuf', 'Fond de veau',
            'Fumet de poisson', 'Fond de volaille',
            // Apéritif
            'Chips', 'Biscuits apéritif', 'Crackers', 'Gressins', 'Cacahuètes', 'Pistaches',
            'Noix de cajou', 'Mélange apéritif', 'Tortilla chips',
            // Fruits secs
            'Amandes', 'Noix', 'Noisettes', 'Pignons de pin', 'Noix de pécan',
            // Monde
            'Feuilles de riz', 'Feuilles de brick', 'Tacos (coques)', 'Kit fajitas', 'Lait de coco light',
            'Algues nori', 'Pâte de miso', 'Vermicelles de riz', 'Galettes de riz soufflé',
        ],
        'Épicerie sucrée' => [
            // Pâtisserie
            'Sucre en poudre', 'Sucre roux', 'Sucre glace', 'Sucre vanillé', 'Sucre en morceaux', 'Cassonade',
            'Farine de blé', 'Farine complète', 'Levure chimique', 'Levure boulangère', 'Bicarbonate de soude',
            'Extrait de vanille', 'Gousse de vanille', 'Chocolat noir pâtissier', 'Chocolat au lait',
            'Chocolat blanc', 'Pépites de chocolat', 'Cacao en poudre', 'Amandes en poudre',
            'Noisettes en poudre', 'Noix de coco râpée', 'Gélatine', 'Agar-agar', 'Arôme fleur d\'oranger',
            'Lait concentré sucré', 'Caramel liquide', 'Pâte d\'amande', 'Préparation pour gâteau',
            'Vermicelles en chocolat', 'Crème anglaise', 'Amandes effilées', 'Sucre de coco', 'Sirop d\'agave',
            // Petit-déjeuner
            'Miel', 'Sirop d\'érable', 'Confiture', 'Pâte à tartiner', 'Beurre de cacahuète', 'Céréales',
            'Muesli', 'Granola', 'Flocons d\'avoine', 'Chocolat en poudre', 'Café moulu', 'Café en grains',
            'Capsules de café', 'Café soluble', 'Thé', 'Infusion', 'Chicorée',
            // Biscuits & gâteaux
            'Biscuits', 'Petits-beurre', 'Boudoirs', 'Spéculoos', 'Madeleines', 'Pain d\'épices',
            'Gâteaux moelleux', 'Barres de céréales', 'Gaufres', 'Cookies', 'Biscuits fourrés',
            'Tablette de chocolat', 'Bonbons',
            // Fruits
            'Compote', 'Crème de marrons', 'Fruits au sirop', 'Ananas au sirop', 'Pêches au sirop',
            'Raisins secs', 'Abricots secs', 'Pruneaux', 'Dattes', 'Figues sèches', 'Cranberries séchées',
            'Marrons cuits',
        ],
        'Condiments & Sauces' => [
            // Sel, poivre, huiles, vinaigres
            'Sel fin', 'Gros sel', 'Fleur de sel', 'Poivre noir', 'Poivre blanc', 'Poivre en grains',
            'Huile d\'olive', 'Huile de tournesol', 'Huile de colza', 'Huile de sésame', 'Huile de noix',
            'Huile de friture', 'Vinaigre de vin', 'Vinaigre balsamique', 'Vinaigre de cidre',
            'Vinaigre de riz', 'Vinaigre de Xérès', 'Vinaigre blanc', 'Crème de vinaigre balsamique',
            // Sauces
            'Moutarde', 'Moutarde à l\'ancienne', 'Mayonnaise', 'Ketchup', 'Sauce barbecue', 'Sauce soja',
            'Sauce soja sucrée', 'Nuoc-mâm', 'Sauce huître', 'Sauce Worcestershire', 'Tabasco', 'Sriracha',
            'Harissa', 'Pesto rouge', 'Sauce béarnaise', 'Sauce burger', 'Sauce samouraï', 'Sauce blanche',
            'Sauce algérienne', 'Vinaigrette', 'Pâte de curry rouge', 'Pâte de curry vert', 'Sauce teriyaki',
            'Sauce aigre-douce', 'Sauce sweet chili', 'Sauce tartare', 'Aïoli', 'Tapenade', 'Wasabi',
            'Sauce hollandaise', 'Sauce au poivre', 'Mirin', 'Sauce satay', 'Ketchup épicé',
            // Bocaux
            'Cornichons', 'Câpres', 'Oignons grelots', 'Piments au vinaigre', 'Ail confit',
            // Épices & aromates secs
            'Herbes de Provence', 'Thym', 'Origan', 'Laurier', 'Romarin', 'Basilic séché', 'Persil séché',
            'Ciboulette séchée', 'Aneth séché', 'Curry', 'Cumin', 'Paprika', 'Paprika fumé', 'Curcuma',
            'Cannelle', 'Muscade', 'Gingembre en poudre', 'Piment d\'Espelette', 'Piment de Cayenne',
            'Piment en flocons', 'Ras el hanout', 'Quatre-épices', 'Colombo', 'Garam masala', 'Tandoori',
            'Épices tex-mex', 'Épices à couscous', 'Clous de girofle', 'Anis étoilé', 'Cardamome', 'Safran',
            'Graines de fenouil', 'Graines de moutarde', 'Baies roses', 'Ail en poudre', 'Oignon en poudre',
            'Oignons frits', 'Bouquet garni', 'Sel de céleri', 'Mélange 5 baies', 'Za\'atar',
            'Échalote déshydratée', 'Sumac', 'Fenugrec', 'Épices pour paella', 'Gomasio',
        ],
        'Surgelés' => [
            'Frites surgelées', 'Potatoes', 'Pommes noisettes', 'Pommes de terre sautées', 'Galettes de pommes de terre',
            'Épinards surgelés', 'Haricots verts surgelés', 'Petits pois surgelés', 'Brocoli surgelé',
            'Chou-fleur surgelé', 'Poêlée de légumes', 'Wok de légumes', 'Mélange de légumes surgelés',
            'Légumes pour couscous', 'Ratatouille surgelée', 'Champignons surgelés', 'Oignons surgelés',
            'Herbes surgelées', 'Ail surgelé', 'Edamame', 'Poivrons surgelés', 'Galettes de légumes',
            'Fruits rouges surgelés', 'Mangue surgelée', 'Glace', 'Sorbet', 'Bâtonnets glacés', 'Pizza surgelée',
            'Poisson pané', 'Filets de poisson surgelés', 'Crevettes surgelées', 'Fruits de mer surgelés',
            'Nuggets de poulet', 'Steaks hachés surgelés', 'Lasagnes surgelées', 'Gratin surgelé',
            'Croissants surgelés', 'Pâte feuilletée surgelée', 'Saint-Jacques surgelées', 'Moules surgelées',
            'Calamars surgelés', 'Cordons bleus surgelés', 'Purée surgelée', 'Soupe surgelée',
        ],
        'Boissons' => [
            'Eau plate', 'Eau gazeuse', 'Jus d\'orange', 'Jus de pomme', 'Jus multifruits', 'Jus de raisin',
            'Jus d\'ananas', 'Jus de tomate', 'Cola', 'Limonade', 'Thé glacé', 'Sirop', 'Tonic',
            'Boisson gazeuse', 'Bière', 'Bière sans alcool', 'Vin rouge', 'Vin blanc', 'Vin rosé', 'Champagne',
            'Crémant', 'Cidre', 'Porto', 'Rhum', 'Whisky', 'Pastis', 'Vodka', 'Gin', 'Martini', 'Vin cuit',
            'Calvados', 'Cognac', 'Liqueur', 'Prosecco', 'Lait chocolaté', 'Smoothie', 'Kombucha',
        ],
    ];
}
