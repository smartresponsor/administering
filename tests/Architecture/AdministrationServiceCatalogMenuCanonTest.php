<?php

declare(strict_types=1);

namespace App\Administering\Tests\Architecture;

use App\Administering\Catalog\Admin\AdministrationServiceSectionCatalog;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceCatalogMenuCanonTest extends TestCase
{
    public function testServiceSectionsMatchCataloguedMenuRoots(): void
    {
        $root = dirname(__DIR__, 2);
        $serviceRoot = $root.'/src/Service';

        $actual = array_values(array_filter(
            scandir($serviceRoot) ?: [],
            static fn (string $entry): bool => !str_starts_with($entry, '.') && is_dir($serviceRoot.'/'.$entry),
        ));
        sort($actual);

        $catalogued = array_map(
            static fn ($section): string => basename($section->serviceDirectory),
            (new AdministrationServiceSectionCatalog())->sections(),
        );
        sort($catalogued);

        self::assertSame($actual, $catalogued);
    }

    public function testEmptyServiceDirectoriesRemainMenuVisibleSections(): void
    {
        $sections = [];
        foreach ((new AdministrationServiceSectionCatalog())->sections() as $section) {
            $sections[$section->key] = $section;
        }

        self::assertArrayHasKey('Administering', $sections);
        self::assertSame('src/Service/Administering', $sections['Administering']->serviceDirectory);
        self::assertSame('AdministrationServiceSectionRecordCrudController', basename(str_replace('\\', '/', $sections['Administering']->primaryCrudControllerClass)));
    }

    public function testEveryServiceSectionUsesOwnPrimaryCrudAnchor(): void
    {
        $forbiddenFallbacks = [
            'Accessing' => ['AdministrationAccountActionRequestRecordCrudController'],
            'Connected' => ['AdministrationConfigSnapshotCrudController'],
            'Environment' => ['AdministrationCredentialStateCrudController'],
            'Managing' => ['AdministrationAclMutationReviewRecordCrudController'],
            'Symfony' => ['AdministrationConfigSnapshotCrudController'],
        ];

        foreach ((new AdministrationServiceSectionCatalog())->sections() as $section) {
            $shortName = basename(str_replace('\\', '/', $section->primaryCrudControllerClass));
            $forbidden = $forbiddenFallbacks[$section->key] ?? [];

            self::assertNotContains($shortName, $forbidden, $section->key.' must not use a borrowed CRUD fallback.');
        }
    }

    public function testServiceMenuRootsUseCrudControllersOnly(): void
    {
        foreach ((new AdministrationServiceSectionCatalog())->sections() as $section) {
            self::assertStringEndsWith('CrudController', $section->primaryCrudControllerClass);
            self::assertTrue(class_exists($section->primaryCrudControllerClass), $section->label);
        }
    }

    public function testMainMenuBuilderUsesEasyAdminCrudIndexLinks(): void
    {
        $builderSource = file_get_contents(dirname(__DIR__, 2).'/src/Builder/Admin/AdministrationMainMenuBuilder.php') ?: '';

        self::assertStringContainsString('MenuItem::linkToCrud', $builderSource);
        self::assertStringContainsString('->setAction(Crud::PAGE_INDEX)', $builderSource);
        self::assertStringNotContainsString('MenuItem::linkTo($section->primaryCrudControllerClass', $builderSource);
        self::assertStringNotContainsString('MenuItem::linkToRoute', $builderSource);
    }
}
