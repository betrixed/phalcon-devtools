<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Tools                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2016 Phalcon Team (https://www.phalconphp.com)      |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Serghei Iakovlev <serghei@phalconphp.com>                     |
  +------------------------------------------------------------------------+
*/

namespace ModTools\Controllers;

use Mod\Schema\Migrations;
use DirectoryIterator;
use Phalcon\Builder\BuilderException;

/**
 * \ModTools\Controllers\MigrationsController
 *
 * @property \Phalcon\Flash\Session $flashSession
 * @property \Phalcon\Flash\Direct $flash
 * @property \Phalcon\Tag $tag
 * 
 * @package ModTools\Controllers
 */
class MigrationsController extends BaseController
{
    /**
     * Initialize controller
     */
    public function initialize()
    {
        parent::initialize();
        $this->view->setVar('page_title', 'Migrations');
    }

    /**
     * @Get("/migrations/list", name="migrations-list")
     */
    public function indexAction()
    {
        $migrations = [];

        if ($migrationsDir = $this->registry->offsetGet('directories')->migrationsDir) {
            foreach (new DirectoryIterator($migrationsDir) as $index => $version) {
                if ($version->isDot() || !$version->isDir()) {
                    continue;
                }

                foreach (new DirectoryIterator($version->getPathname()) as $file) {
                    if ($file->isDot() || $file->isDir() || 'toml' !== strtolower($file->getExtension())) {
                        continue;
                    }

                    $migrations[$version->getBasename()][] = (object) [
                        'name'          => substr($file->getFilename(), 0, -strlen($file->getExtension()) - 1),
                        'filename'      => $file->getFilename(),
                        'size'          => $file->getSize(),
                        'owner'         => $this->fs->getOwner($file),
                        'modified_time' => date('D, d M y H:i:s', $file->getMTime()),
                        'is_writable'   => $file->isWritable(),
                    ];
                }
            }
        }

        if (!empty($migrations)) {
            $keys = array_keys($migrations);
            natsort($keys);

            $tmp = $migrations;
            $migrations = [];
            foreach ($keys as $v) {
                $migrations[$v] = $tmp[$v];
            }
        }

        $this->view->setVars(
            [
                'page_subtitle'  => 'All migrations that we managed to find',
                'migrations'     => $migrations,
                'migrations_dir' => $migrationsDir,
            ]
        );
    }

    /**
     * @Route("/migrations/generate", methods={"POST", "GET"}, name="migrations-generate")
     */
    public function generateAction()
    {
        if ($this->request->isPost()) {
            try {
                Migrations::generate([
                    'directory'       => $this->request->getPost('basePath', 'string'),
                    'tableName'       => $this->request->getPost('tableName', 'string'),
                    'exportData'      => $this->request->getPost('exportDataType', 'string'),
                    'migrationsDir'   => $this->request->getPost('migrationsDir', 'string'),
                    'force'           => $this->request->getPost('force', 'int'),
                    'noAutoIncrement' => $this->request->getPost('noAi', 'int'),
                    'config'          => $this->config,
                    'descr'           => null, // @todo
                    'version'         => $this->request->getPost('version', 'string'),
                ]);

                $this->flashSession->success('The migration was generated successfully.');

                return $this->redirect('migrations/list');
            } catch (BuilderException $e) {
                $this->flash->error($e->getMessage());
            } catch (\Exception $e) {
                $this->flash->error('An unexpected error has occurred. ' . $e->getMessage());
            }
        }

        $migrationsDir = $this->registry->offsetGet('directories')->migrationsDir;
        $basePath = $this->registry->offsetGet('directories')->basePath;

        if (!$migrationsDir) {
            $this->flash->error(
                "Sorry, ModTools doesn't know where the migrations directory is. " .
                "Please add to <code>application</code> section <code>migrationsDir</code> param with real path."
            );
        }

        $this->prepareVersions();

        $this->tag->setDefault('basePath', $basePath);
        $this->tag->setDefault('migrationsDir', $migrationsDir);

        $this->view->setVars(
            [
                'page_subtitle'  => 'Generate Migration',
                'migration_path' => $migrationsDir,
                'tables'         => $this->dbUtils->listTables(true),
            ]
        );
    }

    private function seqByType( $params ) {
        Migrations::run( $params );       
        $this->flashSession->success('The migration was executed successfully.');
        return $this->redirect('migrations/list');
    }
    private function seqByTable( $params )
    {
        Migrations::run( $params );       
        $this->flashSession->success('The migration was executed successfully.');
        return $this->redirect('migrations/list');
    }
    /**
     * @Route("/migrations/run", methods={"POST", "GET"}, name="migrations-run")
     */
    public function runAction()
    {
        if ($this->request->isPost()) {
            
            $params = [
                            'config'        => $this->config,
                            'directory'     => $this->request->getPost('basePath', 'string'),
                            'tableName'     => '@', // @todo
                            'migrationsDir' => $this->request->getPost('migrationsDir', 'string'),
                        ];
             $orderType = $this->request->getPost('orderType','string');
            try {
                if (empty($orderType)) {
                    $this->seqByTable($params);
                }
                else {
                    $this->seqByType($params);
                }
            } catch (BuilderException $e) {
                $this->flash->error($e->getMessage());
            } catch (\Exception $e) {
                $this->flash->error('An unexpected error has occurred. ' . $e->getMessage());
            }
        }

        $migrationsDir = $this->registry->offsetGet('directories')->migrationsDir;
        $basePath = $this->registry->offsetGet('directories')->basePath;

        if (!$migrationsDir) {
            $this->flash->error(
                "Sorry, ModTools doesn't know where the migrations directory is. " .
                "Please add to <code>application</code> section <code>migrationsDir</code> param with real path."
            );
        }

        $this->prepareVersions();

        $this->tag->setDefault('basePath', $basePath);
        $this->tag->setDefault('migrationsDir', $migrationsDir);

        $this->view->setVars(
            [
                'page_subtitle'  => 'Run Migration',
                'migration_path' => $migrationsDir,
            ]
        );
    }

    protected function prepareVersions()
    {
        $migrationsDir = $this->registry->offsetGet('directories')->migrationsDir;

        if (!$migrationsDir || !is_dir($migrationsDir) || !is_readable($migrationsDir)) {
            $this->tag->setDefault('oldVersion', 'None');
            return;
        }

        $folders = [];
        $iterator = new DirectoryIterator($migrationsDir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDot() || !$fileinfo->isDir()) {
                continue;
            }

            $folders[$fileinfo->getFilename()] = $fileinfo->getFilename();
        }

        natsort($folders);
        $folders = array_reverse($folders);
        $foldersKeys = array_keys($folders);

        if (isset($foldersKeys[0])) {
            $this->tag->setDefault('oldVersion', $foldersKeys[0]);
        } else {
            $this->tag->setDefault('oldVersion', 'None');
        }
    }
}
