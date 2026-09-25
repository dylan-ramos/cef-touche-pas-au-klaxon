-- =============================================================================
-- Touche pas au klaxon — Requêtes de contrôle
-- -----------------------------------------------------------------------------
-- À exécuter après 01_schema.sql et 02_seed.sql. Lecture seule.
-- =============================================================================

USE touche_pas_au_klaxon;

-- Volumétrie attendue : 12 agences, 20 employés, 1 administrateur, 16 trajets.
SELECT
    (SELECT COUNT(*) FROM agence)                          AS agences,
    (SELECT COUNT(*) FROM utilisateur WHERE role = 'user')  AS employes,
    (SELECT COUNT(*) FROM utilisateur WHERE role = 'admin') AS administrateurs,
    (SELECT COUNT(*) FROM trajet)                          AS trajets;

-- Page d'accueil : trajets à venir disposant de places, par départ croissant.
-- Résultat attendu : 12 lignes.
SELECT t.id,
       ad.nom                AS depart,
       t.date_heure_depart,
       aa.nom                AS arrivee,
       t.date_heure_arrivee,
       t.places_disponibles,
       CONCAT(u.prenom, ' ', u.nom) AS auteur
FROM trajet t
         INNER JOIN agence ad ON ad.id = t.agence_depart_id
         INNER JOIN agence aa ON aa.id = t.agence_arrivee_id
         INNER JOIN utilisateur u ON u.id = t.auteur_id
WHERE t.date_heure_depart > NOW()
  AND t.places_disponibles > 0
ORDER BY t.date_heure_depart, t.id;

-- Trajets par auteur (le compte de démonstration alexandre.martin en possède 4).
SELECT CONCAT(u.prenom, ' ', u.nom) AS auteur, COUNT(t.id) AS trajets
FROM utilisateur u
         LEFT JOIN trajet t ON t.auteur_id = u.id
GROUP BY u.id, u.prenom, u.nom
HAVING trajets > 0
ORDER BY trajets DESC, auteur;
