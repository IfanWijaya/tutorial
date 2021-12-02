<?php

require('./vendor/autoload.php');

use Litipk\BigNumbers\Decimal as Decimal;
use Web3\Web3;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3p\EthereumTx\Transaction as Tx;
use phpseclib\Math\BigInteger;

function wei2eth($val){
    return Decimal::create($val)->div(Decimal::create(1e18), 18)->floor(8).'';
}
function eth2wei($val){
    return bcmul(Decimal::create($val), Decimal::create(1e18), 0);
}
function dec_to_hex($dec) {
    $sign = ""; // suppress errors
    if ( $dec < 0 ) { $sign = "-"; $dec = abs($dec); }

    $hex = Array( 0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,
                  6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 'a',
                  11 => 'b', 12 => 'c', 13 => 'd', 14 => 'e',   
                  15 => 'f' );
    do {
        $h = $hex[(bcmod($dec,16))] . $h;
        $dec = bcdiv($dec,16,18);

    } while( $dec >= 1 );
   
    return $sign . $h;
} 

class EthTxSender {
    function __construct() {
        $this->pk   = ''; // sender private key
        $this->from = ''; // sender address
        $this->url  = 'https://ropsten.infura.io/v3/9aa3d95b3bc440fa88ea12eaa4456161';
    }
    
    function SendEth($amountInDecimal='', $to='') {
        $web3 = new Web3(new HttpProvider(new HttpRequestManager($this->url, 10)));

        $txHash;
        $web3->eth->getTransactionCount($this->from, function ($err, $result) use (&$ret, $web3, &$txHash, $to, $amountInDecimal) {
            if ( $err !== null ) { throw $err; }
            
            // set nonce
            $nonce = $result;

            // get gas price
            $gasprice_gwei = 10;
            $gasprice = $gasprice_gwei * 1e9;

            // parse amount
            $amountInRaw = eth2wei($amountInDecimal);
            
            // build tx
            $tx = new Tx([
                "nonce"     => '0x' . (preg_match('/^([0-9]{1})$/', $nonce) ? '0' . $nonce : dec_to_hex($nonce)),
                "gasPrice"  => '0x' . dec_to_hex($gasprice),
                "gasLimit"  => '0x' . dec_to_hex(210000),
                "from"      => $this->from,
                "to"        => $to,
                "value"     => '0x' . dec_to_hex($amountInRaw),
                "data"      => '',
                "chainId"   => 3 // ropsten
            ]);
            $rawTx = $tx->sign($this->pk);

            // var_dump($rawTx);exit;

            // broadcast tx
            $web3->eth->sendRawTransaction(
                '0x' . $rawTx,
                function ($err, $result) use (&$txHash) {
                    if ( $err !== null ) { throw $err; }
                    $txHash = $result;
                }
            );
        });

        return $txHash;
    }
}

$sender = new EthTxSender();

$txHash = $sender->SendEth($argv[1], $argv[2]);

echo PHP_EOL;
echo " - Sent. Tx Hash: ".$txHash."\n";
echo PHP_EOL;




