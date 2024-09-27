<?php

error_reporting(E_ALL);
set_exception_handler(function (Throwable $exception) {
    echo str('')
        ->append("Uncaught exception: ", $exception->getMessage(), "\n")
        ->append($exception->getTraceAsString())
        ->wrap('<pre>', '</pre>')
        ->toString();
});

require 'shell.php';

composerRequire([
    'illuminate/collections',
    'illuminate/support',
    'chrome-php/chrome',
    'gregwar/image',
]);

use Gregwar\Image\Image;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Dom\Node;
use Illuminate\Support\Arr;

$filePath = 'armory.png';
$listedLevelThreshold = 80;
$listedThreshold = 500;
$rareThreshold = 540;
$epicThreshold = 600;
$legendaryThreshold = 650;
$fontPath = 'fonts/InriaSans-Bold.ttf';

$data = loadData();
////echo json_encode($data) . "\n<br><br>\n\n". json_encode($data, JSON_PRETTY_PRINT);
////exit();
//$data = json_decode(
//    '[{"name":"Tschubax","race":"Undead","class":"Mage","role":"Damage","level":"80","itemLevel":"608"},{"name":"Kurnous","race":"Undead","class":"Priest","role":"Healer","level":"80","itemLevel":"606"},{"name":"Pfl\u00f6nch","race":"Undead","class":"Monk","role":"Healer","level":"80","itemLevel":"605"},{"name":"Droideka","race":"Zandalari Troll","class":"Druid","role":"Tank","level":"80","itemLevel":"604"},{"name":"Krustenk\u00e4se","race":"Orc","class":"Warrior","role":"Tank","level":"80","itemLevel":"600"},{"name":"Sorunia","race":"Blood Elf","class":"Death Knight","role":"Tank","level":"80","itemLevel":"599"},{"name":"Krustenkase","race":"Orc","class":"Warlock","role":"Damage","level":"80","itemLevel":"598"},{"name":"Fr\u00f6stelts","race":"Orc","class":"Death Knight","role":"Damage","level":"80","itemLevel":"597"},{"name":"Alele","race":"Blood Elf","class":"Priest","role":"Damage","level":"80","itemLevel":"587"},{"name":"P\u00fampernickel","race":"Troll","class":"Shaman","role":"Damage","level":"80","itemLevel":"579"},{"name":"Keinmut","race":"Zandalari Troll","class":"Druid","role":"Damage","level":"80","itemLevel":"577"},{"name":"Kodeqs","race":"Orc","class":"Hunter","role":"Damage","level":"80","itemLevel":"569"},{"name":"Silador","race":"Blood Elf","class":"Paladin","role":"Damage","level":"80","itemLevel":"568"},{"name":"Vertun","race":"Orc","class":"Warrior","role":"Damage","level":"80","itemLevel":"539"},{"name":"Fireprism","race":"Pandaren","class":"Monk","role":"Tank","level":"80","itemLevel":"527"},{"name":"Bobbini","race":"Undead","class":"Priest","role":"Healer","level":"80","itemLevel":"519"},{"name":"Pflonkk","race":"Blood Elf","class":"Demon Hunter","role":"Tank","level":"72","itemLevel":"500"},{"name":"Gradon","race":"Dracthyr","class":"Evoker","role":"Damage","level":"70","itemLevel":"500"},{"name":"Pflonk","race":"Orc","class":"Warrior","role":"Damage","level":"72","itemLevel":"494"},{"name":"Bigfud","race":"Tauren","class":"Druid","role":"Tank","level":"70","itemLevel":"483"},{"name":"Kutti","race":"Undead","class":"Death Knight","role":"Tank","level":"70","itemLevel":"481"},{"name":"Raxax","race":"Goblin","class":"Shaman","role":"Damage","level":"70","itemLevel":"480"},{"name":"Shnipshnap","race":"Goblin","class":"Rogue","role":"Damage","level":"74","itemLevel":"476"},{"name":"Lillith","race":"Troll","class":"Mage","role":"Damage","level":"70","itemLevel":"465"},{"name":"Holycow","race":"Tauren","class":"Paladin","role":"Tank","level":"70","itemLevel":"432"}]',
//    true
//);
//if (isset($_GET['short'])) {
//    $data = json_decode(
//        '[{"name":"Keinmut","race":"Zandalari Troll","class":"Druid","role":"Damage","level":"80","itemLevel":"577"},{"name":"Kodeqs","race":"Orc","class":"Hunter","role":"Damage","level":"80","itemLevel":"569"},{"name":"Silador","race":"Blood Elf","class":"Paladin","role":"Damage","level":"80","itemLevel":"568"},{"name":"Vertun","race":"Orc","class":"Warrior","role":"Damage","level":"80","itemLevel":"539"},{"name":"Fireprism","race":"Pandaren","class":"Monk","role":"Tank","level":"80","itemLevel":"527"},{"name":"Bobbini","race":"Undead","class":"Priest","role":"Healer","level":"80","itemLevel":"519"},{"name":"Pflonkk","race":"Blood Elf","class":"Demon Hunter","role":"Tank","level":"72","itemLevel":"500"},{"name":"Gradon","race":"Dracthyr","class":"Evoker","role":"Damage","level":"70","itemLevel":"500"},{"name":"Pflonk","race":"Orc","class":"Warrior","role":"Damage","level":"72","itemLevel":"494"},{"name":"Bigfud","race":"Tauren","class":"Druid","role":"Tank","level":"70","itemLevel":"483"},{"name":"Kutti","race":"Undead","class":"Death Knight","role":"Tank","level":"70","itemLevel":"481"},{"name":"Raxax","race":"Goblin","class":"Shaman","role":"Damage","level":"70","itemLevel":"480"},{"name":"Shnipshnap","race":"Goblin","class":"Rogue","role":"Damage","level":"74","itemLevel":"476"},{"name":"Lillith","race":"Troll","class":"Mage","role":"Damage","level":"70","itemLevel":"465"},{"name":"Holycow","race":"Tauren","class":"Paladin","role":"Tank","level":"70","itemLevel":"432"}]',
//        true
//    );
//}
$data = filterData($data, $listedThreshold, $listedLevelThreshold);
writeImage($filePath, $data, $rareThreshold, $epicThreshold, $legendaryThreshold, $fontPath);
outputImage($filePath);

function loadData(): array
{
    $browserFactory = new BrowserFactory('chromium');
    $browserFactory->setOptions([
        'sendSyncDefaultTimeout' => 30000, // defaults to 5000
        'userAgent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9',
        //'debugLogger' => 'php://stdout',
        'headless' => true,
        'noSandbox' => true,
        'customFlags' => ['--disable-dev-shm-usage'] # because shm is limited in docker, this fixed the major issue of not processing
    ]);
    $browser = $browserFactory->createBrowser();

    try {
        $page = $browser->createPage();
        $page->navigate('https://worldofwarcraft.blizzard.com/en-gb/guild/eu/arygos/famboot?page=1&view=item-level&sort-column=5&sort-descending=false');
        try {
            $page->waitUntilContainsElement('.GuildProfileRoster-table');
        } catch (Exception) {
            // Because we stil want to continue
        }

        $rows = $page->dom()
            ->querySelector('.GuildProfileRoster-table')
            ->querySelector('.ControlledTable-body')
            ->querySelectorAll('.ControlledTable-row');
        if (count($rows) === 0) {
            throw new RuntimeException("Could not fetch any rows");
        }

        return Arr::map($rows, fn (Node $row) => [
            'name' => $row->querySelector('.ControlledTable-col:nth-child(1)')->getAttribute('data-value'),
            'race' => $row->querySelector('.ControlledTable-col:nth-child(2)')->getAttribute('data-value'),
            'class' => $row->querySelector('.ControlledTable-col:nth-child(3)')->getAttribute('data-value'),
            'role' => $row->querySelector('.ControlledTable-col:nth-child(4)')->getAttribute('data-value'),
            'level' => $row->querySelector('.ControlledTable-col:nth-child(5)')->getAttribute('data-value'),
            'itemLevel' => $row->querySelector('.ControlledTable-col:nth-child(6)')->getAttribute('data-value'),
        ]);
    } finally {
        $browser->close();
    }
}

function filterData(array $data, int $listedThreshold, int $listedLevelThreshold): array
{
    $filtered = Arr::where($data, fn ($entry) => //
        $entry['itemLevel'] >= $listedThreshold &&
        $entry['level'] >= $listedLevelThreshold);

    return array_values($filtered); // clear indexes
}

function writeImage(string $filePath, array $data, $rareThreshold, $epicThreshold, int $legendaryThreshold, string $fontPath): void
{
    $height = (1 + count($data)) * 20;

    $image = Image::create(170, $height); // TODO was 150
    prepareImageStyle($image);

    foreach ($data as $i => $entry) {
        $y = 25 + $i * 20;
        $itemRarityColor = match (true) {
            $entry['itemLevel'] >= $legendaryThreshold => 0xff8000,
            $entry['itemLevel'] >= $epicThreshold => 0xa335ee,
            $entry['itemLevel'] >= $rareThreshold => 0x0070dd,
            default => 0x1eff00,
        };
        $roleImagePath = "images/roles/{$entry['role']}.png";
        if (file_exists($roleImagePath)) {
            $role = Image::open($roleImagePath)->scaleResize(height: 15);
            $image->merge($role, 10, $y - 13);
        }

        $image->write($fontPath, $entry['itemLevel'], 30, $y, size: 10, color: $itemRarityColor);
        $image->write($fontPath, "- {$entry['name']}", 55, $y, size: 10, color: getClassColor($entry['class']));
    }

    $success = $image->save($filePath, str($filePath)->afterLast('.')->toString());
    if ($success === false) {
        throw new RuntimeException("Could not save image to $filePath");
    }
}

function prepareImageStyle(Image $image): void
{
    $background = Image::open('images/wow_bg.png')->scaleResize(height: $image->height());
    $backgroundScalingFactor = $image->height() / $background->height();
    $backgroundOffsetX =
        ($background->width() - 890) * $backgroundScalingFactor // offset in the background image
        - $image->width() // make the offset relative to the right of the image
    ;
    $image->merge($background, -$backgroundOffsetX);

    // 76% black overlay is good
    // 76% overlay results in 24% opacity
    // 24% opacity of maximum 127 in hex 0x1E
    $overlay = Image::create($image->width(), $image->height())->fill(0x1E000000);
    $image->merge($overlay);

    $icon = Image::open('images/icon.png')->scaleResize(width: 30);
    $image->merge($icon, 134, $image->height() - 41);
}

function getClassColor($class): int
{
    return match ($class) {
        'Death Knight' => 0xC41E3A,
        'Demon Hunter' => 0xA330C9,
        'Druid' => 0xFF7C0A,
        'Evoker' => 0x33937F,
        'Hunter' => 0xAAD372,
        'Mage' => 0x3FC7EB,
        'Monk' => 0x00FF98,
        'Paladin' => 0xF48CBA,
        //'Priest' => 0x0, // normally: 0xFFFFFF, but does not render too well on a white bg
        'Priest' => 0xFFFFFF,
        'Rogue' => 0xFFF468,
        'Shaman' => 0x0070DD,
        'Warlock' => 0x8788EE,
        'Warrior' => 0xC69B6D,
        default => 0x000000,
    };
}

function outputImage(string $filePath): void
{
    header('Content-Type', 'image/png');
    echo file_get_contents($filePath);
}
