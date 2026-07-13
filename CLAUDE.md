# forsvar_webhooks — lineamientos

## Cambios de schema de DB → anti-pattern CONGELADO (DDL en runtime)

forsvar_webhooks crea tablas en **runtime** (`datahandler/mysql.php`, familias `orm_*`
dinámicas keyed-on-tenant). Es un anti-pattern conocido y **congelado** (tech-debt).

**Reglas duras:**
- **Prohibido** agregar NUEVO `CREATE TABLE` / `ALTER TABLE` en runtime. La superficie de
  DDL dinámico actual queda como está.
- Cualquier necesidad nueva de schema es señal para **abrir el proyecto de migración a
  dbmate** (rediseño de las tablas dinámicas a un schema fijo + baseline por tenant), no
  para agregar más DDL en runtime.
- Tablas de otros dominios → migración dbmate en su repo DUEÑO (engine/frontend/onboarding).

Convención general: `forsvar_terraform/k8s/base/jobs/README.md`.
Regla para Cursor: `.cursor/rules/db-migrations.mdc`.
