<?php
namespace Topxia\Common;

use Topxia\Service\Common\ServiceKernel;

class BlockToolkit
{

    public static function init($code, $jsonFile)
    {
        if (file_exists($jsonFile)) {
            $blockMeta = json_decode(file_get_contents($jsonFile), true);
            if (empty($blockMeta)) {
                throw new \RuntimeException("插件元信息文件{$blockMeta}格式不符合JSON规范，解析失败，请检查元信息文件格式");
            }

            $blockService = ServiceKernel::instance()->createService('Content.BlockService');
            foreach ($blockMeta as $key => $meta) {
                $block = $blockService->getBlockByCode($key);
                $default = array();
                foreach ($meta['items'] as $key => $item) {
                    $default[$key]['items'] = $item['default'];
                    $default[$key]['type'] = $item['type'];
                }
                if (empty($block)) {
                    $block = array(
                        'code' => $key,
                        'category' => $code,
                        'meta' => $meta,
                        'data' => $default,
                        'templateName' => $meta['templateName'],
                        'title' => $meta['title'],
                    );
                    $blockService->createBlock($block);
                } else {
                    $blockService->updateBlock($block['id'], array(
                        'category' => $code,
                        'meta' => $meta,
                        'data' => $default,
                        'templateName' => $meta['templateName'],
                        'title' => $meta['title'],
                    ));
                }
            }
        }
    }

    public static function updateCarousel($code)
    {
        $blockService = ServiceKernel::instance()->createService('Content.BlockService');
        $block = $blockService->getBlockByCode($code);
        $data = $block['data'];
        $content = $block['content'];
        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $content, $imgMatchs);
        preg_match_all('/< *img[^>]*alt *= *["\']?([^"\']*)/i', $content, $altMatchs);
        preg_match_all('/< *a[^>]*href *= *["\']?([^"\']*)/i', $content, $linkMatchs);
        preg_match_all('/< *a[^>]*target *= *["\']?([^"\']*)/i', $content, $targetMatchs);
        foreach ($data['carousel']['items'] as $key => $imglink) {
            if (!empty($imgMatchs[1][$key])) {
                $imglink['src'] = $imgMatchs[1][$key];
            }

            if (!empty($altMatchs[1][$key])) {
                $imglink['alt'] = $altMatchs[1][$key];
            }

            if (!empty($linkMatchs[1][$key])) {
                $imglink['href'] = $linkMatchs[1][$key];
            }

            if (!empty($targetMatchs[1][$key])) {
                $imglink['target'] = $targetMatchs[1][$key];
            }

            $data['carousel']['items'][$key] = $imglink;
        }

        $blockService->updateBlock($block['id'], array(
            'data' => $data,
        ));
    }

    public static function updateLinks($code)
    {
        $blockService = ServiceKernel::instance()->createService('Content.BlockService');
        $block = $blockService->getBlockByCode($code);
        $data = $block['data'];
        $content = $block['content'];
        preg_match_all('/<dt>(.*?)<\/dt>/i', $content, $textMatchs);
        preg_match_all('/<dl>.*?<\/dl>/i', $content, $dlMatchs);
        $index = 0;
        $index2 = 0;
        foreach ($data as $key => &$object) {
            if ($object['type'] == 'text') {
                $object['items'][0]['value'] = $textMatchs[1][$index++];
            }

            if ($object['type'] == 'link' && !empty($dl = $dlMatchs[0][$index2++])) {
                preg_match_all('/< *a[^>]*href *= *["\']?([^"\']*)/i', $dl, $hrefMatchs);
                preg_match_all('/< *a[^>]*target *= *["\']?([^"\']*)/i', $dl, $targetMatchs);
                preg_match_all('/< *a.*?>(.*?)<\/a>/i', $dl, $valuetMatchs);
                foreach ($object['items'] as $i => &$item) {
                    if (!empty($hrefMatchs[1][$i])) {
                        $item['href'] = $hrefMatchs[1][$i];
                    }

                    if (!empty($targetMatchs[1][$i])) {
                        $item['target'] = $targetMatchs[1][$i];
                    }

                    if (!empty($valuetMatchs[1][$i])) {
                        $item['value'] = $valuetMatchs[1][$i];
                    }
                }
            }
        }

        $blockService->updateBlock($block['id'], array(
            'data' => $data,
        ));
    }
}