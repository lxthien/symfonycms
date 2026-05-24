<?php

namespace App\Category;

use App\Entity\NewsCategory;

class NewsCategoryTreeBuilder
{
    public function flatten(array $categories)
    {
        $byId = [];
        $childrenByParent = [];

        foreach ($categories as $category) {
            if (!$category instanceof NewsCategory) {
                continue;
            }

            $byId[$category->getId()] = $category;
            $parentId = $this->getParentId($category);

            if ($parentId && !isset($byId[$parentId])) {
                $childrenByParent[$parentId][] = $category;
                continue;
            }

            $childrenByParent[$parentId ?: 0][] = $category;
        }

        foreach ($childrenByParent as &$children) {
            $this->sortCategories($children);
        }
        unset($children);

        $rows = [];
        $visited = [];

        $this->appendChildren(0, $childrenByParent, $rows, $visited, 0, []);

        foreach ($categories as $category) {
            if (!$category instanceof NewsCategory || isset($visited[$category->getId()])) {
                continue;
            }

            $this->appendCategory($category, $childrenByParent, $rows, $visited, 0, [], true);
        }

        return $rows;
    }

    private function appendChildren($parentId, array $childrenByParent, array &$rows, array &$visited, $depth, array $path)
    {
        if (empty($childrenByParent[$parentId])) {
            return;
        }

        foreach ($childrenByParent[$parentId] as $category) {
            $this->appendCategory($category, $childrenByParent, $rows, $visited, $depth, $path, false);
        }
    }

    private function appendCategory(NewsCategory $category, array $childrenByParent, array &$rows, array &$visited, $depth, array $path, $orphan)
    {
        $id = $category->getId();

        if (isset($visited[$id])) {
            return;
        }

        $hasCycle = in_array($id, $path, true);
        $visited[$id] = true;

        $children = isset($childrenByParent[$id]) ? $childrenByParent[$id] : [];
        $parent = $category->getParentcat();

        $rows[] = [
            'category' => $category,
            'depth' => $depth,
            'parent' => $parent instanceof NewsCategory ? $parent : null,
            'childrenCount' => count($children),
            'isOrphan' => $orphan,
            'hasCycle' => $hasCycle,
        ];

        if ($hasCycle) {
            return;
        }

        $path[] = $id;
        $this->appendChildren($id, $childrenByParent, $rows, $visited, $depth + 1, $path);
    }

    private function getParentId(NewsCategory $category)
    {
        $parent = $category->getParentcat();

        return $parent instanceof NewsCategory ? $parent->getId() : null;
    }

    private function sortCategories(array &$categories)
    {
        usort($categories, function (NewsCategory $left, NewsCategory $right) {
            $nameComparison = strnatcasecmp((string) $left->getName(), (string) $right->getName());

            if ($nameComparison !== 0) {
                return $nameComparison;
            }

            return $left->getId() <=> $right->getId();
        });
    }
}
