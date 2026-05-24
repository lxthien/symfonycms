# Symfony Upgrade Roadmap

Target path:

```text
3.3 -> 3.4 -> 4.4 -> 5.4 -> 6.4
```

Do not jump directly from Symfony 3.3 to 6.4. The safe process is to move to the last minor of each major line, remove deprecations, then continue.

## Readiness Command

Run before and after each refactor:

```bash
php bin/console app:upgrade-readiness --env=dev
```

For a specific phase:

```bash
php bin/console app:upgrade-readiness --target=3.4 --env=dev
php bin/console app:upgrade-readiness --target=4.4 --env=dev
php bin/console app:upgrade-readiness --target=6.4 --env=dev
```

The command audits known blockers in:

- `composer.json`
- legacy Symfony 3 directory structure
- FOSUser/security config
- bundles that must be upgraded or replaced

## Phase 0: Baseline

- Keep feature work small while upgrading.
- Keep migrations as the source of truth.
- Run:

```bash
composer validate --no-check-publish
php bin/console app:upgrade-readiness --env=dev
php bin/console debug:router --env=dev
php bin/console debug:container --env=dev
vendor/bin/phpunit --testsuite "Project Test Suite"
```

## Phase 1: Symfony 3.3 to 3.4

- Change Symfony constraint to `3.4.*`.
- Keep PHP platform at a version compatible with both current production and Symfony 3.4.
- Fix deprecations reported by Symfony PHPUnit Bridge.
- Do not move directories yet.
- Status: completed on local. The app reached Symfony `3.4.49` before moving to 4.4.

## Phase 2: Symfony 3.4 to 4.4

- Status: completed on local. The app now runs on Symfony `4.4.51`.
- Verified:

```bash
composer validate --no-check-publish
php bin/console --version
php bin/console debug:router --env=dev
php bin/console doctrine:migrations:status --env=dev
php bin/console doctrine:schema:validate --env=dev
vendor/bin/phpunit --testsuite "Project Test Suite"
```

- Completed package work:
  - Removed Sensio DistributionBundle and its Composer script handlers.
  - Upgraded Doctrine DBAL/ORM/migrations to Symfony 4.4-compatible bridge versions.
  - Updated Doctrine migration classes to the Doctrine Migrations 2 namespace/signature.
  - Upgraded Liip Imagine Bundle and changed its route import to `routing.yaml`.
  - Upgraded Symfony Swiftmailer Bundle to the Symfony 4.4-compatible line.
  - Removed composer-parameter-handler; production must provide `config/parameters.yml` or env values explicitly.

Remaining modernization work while staying on 4.4:

- Move toward Flex structure:
  - `AppKernel` -> `src/Kernel.php`: completed. Legacy `app/AppKernel.php` and `app/AppCache.php` removed.
  - `config/bundles.php`: added as the bundle registry.
  - Entry points now boot `App\Kernel`.
  - `app/config` -> `config`: runtime config now loads from `config/config_{env}.yml`; local secrets moved to ignored `config/parameters.yml`.
  - `app/Resources/views` -> `templates`: completed and legacy view directory removed. Bundle overrides now live under `templates/bundles/...`.
  - `web` -> `public`: completed for runtime config/build/upload paths. Static assets/uploads were mirrored into `public/` and legacy `web/` is no longer part of runtime.
  - `src/AppBundle` -> `src`
- Replace abandoned/deprecated packages gradually: `twig/extensions`, Sensio FrameworkExtra annotations, legacy Security encoders.
- Upgrade Vich before Symfony 6.4 if moving to PHP 8.1+.

## Phase 3: Symfony 4.4 to 5.4

- Status: runtime boot completed on local. The app reports Symfony `5.4.51`.
- Completed preparation:
  - Composer PHP platform raised to `7.2.5`, the Symfony 5.4 minimum.
  - DoctrineBundle, Doctrine Migrations Bundle, Doctrine ORM/DBAL, Doctrine Persistence upgraded to Symfony 5-compatible lines.
  - Doctrine migration metadata upgraded with `doctrine:migrations:sync-metadata-storage` on dev.
  - `Doctrine\Common\Persistence` usages replaced with `Doctrine\Persistence`/`Doctrine\ORM\EntityManagerInterface`.
  - Knp Menu/Paginator, Stof Doctrine Extensions, Sensio FrameworkExtra, Monolog, PHPUnit, PHPUnit Bridge upgraded.
  - WhiteOctober Breadcrumbs Bundle replaced by an internal breadcrumb manager/Twig extension.
  - EWZ Recaptcha Bundle replaced by an internal Recaptcha form type and validator.
  - FOSUserBundle removed. `User` now implements Symfony Security user interfaces directly, security routes are handled by `SecurityController`, and `AppBundle\Security\UserProvider` loads users from Doctrine.
  - `app/Resources/views` moved to `templates/` and Twig default path points to `templates/`.
  - Bundle overrides copied to Symfony-style `templates/bundles/...` paths for Twig and WhiteOctober breadcrumb templates.
  - Public document root migration completed with `public/`.
  - Swiftmailer replaced by Symfony Mailer config.
  - StarRatingBundle removed and replaced by a local rating form type/Twig filter.
  - `twig/extensions` removed. The only used legacy filter, `localizeddate`, is now provided by `AppBundle\Twig\AppExtension`.
  - Security config now uses `password_hashers`, lazy firewall, and Symfony 5.4 authenticator manager; user creation/password update uses `UserPasswordHasherInterface`.
  - Liip Imagine Twig mode changed to `lazy` to remove deprecated templating filter extension usage.
  - `framework.templating` and old TwigBundle error routes removed/replaced.
  - Legacy controller base class replaced with `AbstractController`.
  - Started reducing Sensio FrameworkExtra usage:
    - `Admin\UserController` now uses Symfony native route annotations, route-level `methods`, and explicit access checks instead of `@Method`/`@Security`.
    - `SecurityController` now uses Symfony native route annotations and route-level `methods`.
    - Report/settings controllers converted: `Admin\AuditLogController`, `Admin\DashboardController`, `Admin\HealthController`, `Admin\ContentDecayController`, `Admin\SettingController`.
    - `Admin\ContactController` converted and keeps explicit permission checks for list/delete.
    - CRUD controllers converted: `Admin\SeoRedirectController`, `Admin\BannerController`, `Admin\BannerCategoryController`, `Admin\TagController`.
    - Removed a stale `@Security("is_granted('delete', post)")` expression from tag delete and replaced it with explicit `CMS_CONTENT_EDIT`.
    - Remaining CRUD controllers converted: `Admin\MediaController`, `Admin\NewsCategoryController`, `Admin\CommentController`, `Admin\PageController`, `Admin\NewsController`.
    - `@Method`/`@Security` and Sensio route imports are no longer used in controllers.
    - ParamConverter-style entity injection has been replaced by explicit repository loading in admin route actions.
    - `sensio/framework-extra-bundle` has been removed from Composer and `config/bundles.php`.
    - Storefront route imports switched to Symfony native route annotations where applicable.

- Verified on local:

```bash
composer validate --no-check-publish
php bin/console --version
php bin/console lint:yaml config --env=dev
php bin/console lint:twig templates --env=dev
php bin/console debug:router --env=dev
php bin/console doctrine:schema:validate --env=dev
php bin/console cache:warmup --env=prod --no-debug
```

- Remaining 5.4 modernization work:
  - Exercise login/logout/user-management flows manually on staging after the authenticator-manager switch.
  - Continue moving independent infrastructure classes from `AppBundle\` to `App\` namespace.
    - Upgrade readiness checker/command moved to `src/Upgrade` and `src/Command`.
    - Security infrastructure moved to `src/Security`: user provider, user checker, admin capability constants, and capability voter.
    - Independent managers moved to `src/Health` and `src/InternalLink`.
    - SEO services and category tree builder moved to `src/Seo` and `src/Category`.
    - Revision and audit managers moved to `src/Revision` and `src/Audit`.
    - Utilities, media services, settings/schema services, and breadcrumb manager moved to `src/Utils`, `src/Media`, `src/Settings`, `src/Schema`, and `src/Breadcrumb`.
    - Twig extensions, validators, and console commands moved to `src/Twig`, `src/Validator`, and `src/Command`.
    - Event listeners/subscribers moved to `src/EventListener`.
    - Analytics tracker, menu builder, and fixtures moved to `src/Analytics`, `src/Menu`, and `src/DataFixtures`.
    - Form types and form data transformers moved to `src/Form`.
    - Doctrine repositories moved to `src/Repository`; entity `repositoryClass` annotations now point to `App\Repository`.
    - Doctrine entities moved to `src/Entity`; Doctrine mapping now points to `App\Entity` and legacy DQL aliases were replaced with FQCNs.
    - Controllers moved to `src/Controller`; route resources and controller references now use `App\Controller\...::action`.
    - The legacy `AppBundle` bundle class/registration was removed.
  - Reduce `src/AppBundle` namespace gradually.

## Phase 4: Runtime Upgrade

- Upgrade local/staging/production to PHP 8.1+ before Symfony 6.4.
- Update Composer platform to PHP 8.1+.
- Fix PHP 8 strictness issues.
- Status: completed on local with PHP `8.1.25`.
- Completed compatibility fixes:
  - Composer platform now targets PHP `8.1.25`.
  - Security user provider/checker and voter signatures updated for Symfony 6.
  - `User` now implements `PasswordAuthenticatedUserInterface` and `getUserIdentifier()`.
  - Twig 3 compatibility verified; legacy `{% spaceless %}` tags removed from breadcrumb templates.

## Phase 5: Symfony 5.4 to 6.4

- Update Symfony packages to `6.4.*`.
- Remove remaining deprecated APIs.
- Finalize native Security/authenticator manager.
- Verify all admin and storefront smoke tests.
- Status: runtime boot completed on local. The app reports Symfony `6.4.40`.
- Completed package work:
  - Symfony packages upgraded to `6.4.*`.
  - Twig upgraded to `3.x`.
  - VichUploaderBundle upgraded to `2.9.x`.
  - KnpPaginatorBundle/KnpComponents upgraded to Symfony 6-compatible lines.
  - PHPUnit upgraded to `9.6.x` and `phpunit.xml.dist` migrated to the current PHPUnit schema.

- Verified on local:

```bash
composer validate --no-check-publish
composer dump-autoload
php bin/console --version
php bin/console lint:yaml config --env=dev
php bin/console lint:twig templates --env=dev
php bin/console debug:router --env=dev
php bin/console doctrine:mapping:info --env=dev
php bin/console doctrine:schema:validate --env=dev
php bin/console app:upgrade-readiness --target=6.4 --env=dev
php bin/console cache:warmup --env=prod --no-debug
vendor/bin/phpunit --testsuite "Project Test Suite"
```

- Remaining before release/staging:
  - Manually smoke test login/logout, admin CRUD, upload/media picker, comments, scheduled publishing, sitemap/schema, and storefront post/category pages.
  - Deploy with `public/` as the document root.
