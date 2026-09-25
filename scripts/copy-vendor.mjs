// Copie dans public/assets les ressources front fournies par les paquets npm
// (JavaScript Bootstrap, polices d'icônes) : aucune dépendance à un CDN.
import { cpSync, mkdirSync } from 'node:fs';

const copies = [
  ['node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', 'public/assets/js/vendor/bootstrap.bundle.min.js'],
  ['node_modules/bootstrap-icons/font/fonts', 'public/assets/fonts'],
];

for (const [from, to] of copies) {
  mkdirSync(to.endsWith('.js') ? to.slice(0, to.lastIndexOf('/')) : to, { recursive: true });
  cpSync(from, to, { recursive: true });
}
