# MesaLista

Sistema web de punto de venta y gestión para restaurantes, en la nube.

Proyecto de la materia **Tópicos Avanzados de Desarrollo Web (IDD-2504)** — Instituto Tecnológico de Celaya.

**Autor:** Ricardo Rodríguez Arellano

## Descripción

MesaLista reemplaza un punto de venta de escritorio con una aplicación web que funciona desde cualquier navegador (computadora, tablet o celular), con la información en la nube y respaldos automáticos. Incluye croquis de mesas en tiempo real, toma de comandas, pantalla de cocina, cobro con ticket, autofacturación CFDI 4.0, asistencia del personal y reportes.

## Tecnologías

| Capa | Tecnología |
|------|------------|
| Frontend | React + Vite + TypeScript (PWA) — Vercel |
| Backend | Laravel (PHP 8), Sanctum, Socialite — Coolify |
| Base de datos | Supabase (PostgreSQL, Realtime, Storage) |
| Servicios externos | Facturapi (sandbox), Google OAuth 2.0, SMTP |

## Estructura del repositorio

```
MesaLista/
├── backend/    API REST en Laravel
├── frontend/   Aplicación React
└── docs/       Diagramas, documentación de la API y manuales
```

## Flujo de trabajo

- `main`: producción
- `develop`: integración
- `feature/<modulo>`: una rama por módulo, integrada a `develop` mediante pull request

Los mensajes de commit siguen el formato `tipo: descripción` (`feat`, `fix`, `docs`, `test`, `chore`).

## Instalación

Las instrucciones para ejecutar el backend y el frontend se agregarán conforme se creen.
