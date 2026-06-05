<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Admin;

use App\Administering\Catalog\Admin\AdministrationFilesystemServiceToolCatalog;
use App\Administering\Catalog\Admin\AdministrationServiceSectionCatalog;
use App\Administering\Catalog\Admin\AdministrationServiceToolScreenCatalog;
use PHPUnit\Framework\TestCase;

final class AdministrationServiceToolCatalogTest extends TestCase
{
    public function testEveryDirectServiceFileIsCataloguedAsSectionTool(): void
    {
        $catalog = new AdministrationFilesystemServiceToolCatalog(new AdministrationServiceSectionCatalog(), new AdministrationServiceToolScreenCatalog());
        $catalogued = [];

        foreach ($catalog->tools() as $tool) {
            $catalogued[] = $tool->serviceClass;
        }
        sort($catalogued);

        $root = dirname(__DIR__, 3);
        $expected = [];
        foreach ((new AdministrationServiceSectionCatalog())->sections() as $section) {
            $directory = $root.'/'.$section->serviceDirectory;
            foreach (scandir($directory) ?: [] as $file) {
                if (str_ends_with($file, '.php')) {
                    $expected[] = 'App\\Administering\\Service\\'.$section->key.'\\'.basename($file, '.php');
                }
            }
        }
        sort($expected);

        self::assertSame($expected, $catalogued);
    }

    public function testManagingToolsAreNotCataloguedUnderRolling(): void
    {
        $catalog = new AdministrationFilesystemServiceToolCatalog(new AdministrationServiceSectionCatalog(), new AdministrationServiceToolScreenCatalog());

        foreach ($catalog->toolsForSection('Rolling') as $tool) {
            self::assertStringNotContainsString('ManagingField', $tool->shortName);
        }

        self::assertNotEmpty($catalog->toolsForSection('Managing'));
    }
}
