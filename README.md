# Administering EasyAdmin render restore

This patch restores Administering admin controllers to native Symfony/EasyAdmin `Response` rendering.

Scope:
- removes the accidental Viewing/surface-array return style from EasyAdmin/admin routes;
- restores `AbstractDashboardController` for the EasyAdmin dashboard;
- restores `render(...)`, `redirect(...)`, and `redirectToRoute(...)` responses;
- keeps EasyAdmin templates under `@Administering/easy_admin/...` and `@Administering/administering/...` as the render target.

Boundary:
- Cruding / Viewing / Interfacing must not capture EasyAdmin admin routes or controllers.
