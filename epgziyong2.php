<?php
// 定义要合并的XML文件列表
$xmlFiles = [
    './epgmytvsuper.xml',
    './epganywhere.xml',
    './epgkai.xml',
    './epg4gtv2.xml', 
    './epgnew51zmt.xml', 
    './epgnewshanghai.xml',
    './epgnewhebei.xml',
    './epgnewguangdong.xml',
    './epgyidong.xml'
    // './epgastro.xml',
    // './epgunifi.xml',
];

// 创建新的SimpleXMLElement对象作为根元素
$mergedXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tv></tv>');

// 存储已添加的频道 ID
$channelIds = [];
// 存储频道 ID 对应的 SimpleXMLElement 引用
$channelMap = [];
// 存储已添加的节目 key（start+stop+channel），避免重复
$programmeKeys = [];

/**
 * 生成节目的唯一 key
 */
function programmeKey($programme) {
    return (string)$programme['start'] . '|' . (string)$programme['stop'] . '|' . (string)$programme['channel'];
}

foreach ($xmlFiles as $file) {
    // 检查文件是否存在
    if (!file_exists($file)) {
        error_log("跳过不存在的文件: $file");
        continue;
    }

    // 加载XML文件并检查错误
    $xml = simplexml_load_file($file);
    if ($xml === false) {
        error_log("加载失败: $file");
        continue;
    }

    // 合并<channel>元素（去重 + 多 display-name 保留）
    if (isset($xml->channel)) {
        foreach ($xml->channel as $channel) {
            $id = (string)$channel['id'];
            $displayName = htmlspecialchars((string)$channel->{'display-name'}, ENT_XML1);
            $lang = (string)$channel->{'display-name'}['lang'];

            if (!isset($channelIds[$id])) {
                // 新频道：直接添加
                $channelIds[$id] = true;
                $mergedChannel = $mergedXml->addChild('channel');
                $mergedChannel->addAttribute('id', $id);
                $mergedChannel->addChild('display-name', $displayName)->addAttribute('lang', $lang);
                $channelMap[$id] = $mergedChannel;
            } else {
                // 已存在：检查是否已有相同的 display-name
                $exists = false;
                foreach ($channelMap[$id]->{'display-name'} as $existingDisplay) {
                    if ((string)$existingDisplay === $displayName) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $channelMap[$id]->addChild('display-name', $displayName)->addAttribute('lang', $lang);
                }
            }
        }
    }
}
foreach ($xmlFiles as $file) {
    // 检查文件是否存在
    if (!file_exists($file)) {
        error_log("跳过不存在的文件: $file");
        continue;
    }

    // 加载XML文件并检查错误
    $xml = simplexml_load_file($file);
    if ($xml === false) {
        error_log("加载失败: $file");
        continue;
    }
    // 合并<programme>元素（去重）
    if (isset($xml->programme)) {
        foreach ($xml->programme as $programme) {
            $key = programmeKey($programme);
            if (!isset($programmeKeys[$key])) {
                $programmeKeys[$key] = true;
                $mergedProgramme = $mergedXml->addChild('programme');
                $mergedProgramme->addAttribute('start', (string)$programme['start']);
                $mergedProgramme->addAttribute('stop', (string)$programme['stop']);
                $mergedProgramme->addAttribute('channel', (string)$programme['channel']);

                $title = htmlspecialchars((string)$programme->title, ENT_XML1);
                $mergedProgramme->addChild('title', $title)
                                ->addAttribute('lang', (string)$programme->title['lang']);

                $desc = htmlspecialchars((string)$programme->desc, ENT_XML1);
                $mergedProgramme->addChild('desc', $desc)
                                ->addAttribute('lang', (string)$programme->desc['lang']);
            }
        }
    }
}

// 使用DOMDocument进行格式化输出
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($mergedXml->asXML());
$dom->save('epgziyong.xml');

// 生成 gz 压缩文件
$xmlContent = file_get_contents('epgziyong.xml');
$gz = gzopen('epgziyong.xml.gz', 'w9');  // 'w9' 表示最高压缩级别
if ($gz !== false) {
    gzwrite($gz, $xmlContent);
    gzclose($gz);
    echo "XML文件合并完成，已保存为 epgziyong.xml 和 epgziyong.xml.gz";
} else {
    echo "创建 gz 文件失败";
}
?>
