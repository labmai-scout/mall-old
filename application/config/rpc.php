<?php

//rpc请求使用的server_name
$config['server_name'] = 'mall';

//本地服务器名称
$config['private_key'] = '
-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQDGUdG5ysZ20qRxvn7aBl9oZCjOlZ430zwlhyVPGaQAKZ2MKqmw
PXZ8wrXQBDo/J9Mc6ecVPiPQN6yVglti1JQ+Psxha5zAYkZ5+btPUrUDxvFHgZ0q
V9X3ymq2RZQWSejCTSZOghuZulU/IKl+RP6AE3KHPOTFAob5hloT0paVCQIDAQAB
AoGAI5bxTSc/oHlCu4rVFulH2+MFk7Uz9I663302S9CtJr5RIKNTWmZdShxjJlZr
4YOTFkA/kQdaw/YJybbgEYdWptf6T5CGRyTTcC0tAhKs1vgyjHSJQRnKLNYetH6N
X4bfN7wJhdrVsZ46VEL8wNCZzgAv30+FZowbpXF8GmD9p4ECQQDuwIsLUsK8Ers4
N45sueJX3fHUme3XFo78qsTAYq9OytJIIK91AXw718MiQ94McgfPNRMkCaqDo7ML
SVod/+0RAkEA1KWFeOkoRDeCwMrWsaIy7wKENiZw9cg2Gi7lwrWA6/N1DZet422l
hFMor2IJ5Cq5OBM8mtUB3cPrRlWIjccIeQJAKz/18Dctz6QVBjoKMuf5eLFb/Ydk
7nHHtT26Jp+54iwbq7VAE5IRT0Xms25X6yk9AOw8a2rU2MPuyzyedpDGAQJABpst
zlfP/G6NDVg/2zziwDIf0V7YW4pgw+d5E9d3rdzeYhG4QTyCy92ZgflVvVTmdCuE
0nqTmEQh5wl5OI5aYQJBAK3PAcQJIZahhuzK1gUKAHfgGSoDXrLAdYBR5FmlEesm
WjvVq4k2j5YalOQ7ocohSzIMZeOcG5OkoiVIrxmjGtw=
-----END RSA PRIVATE KEY-----';

$config['servers']['wiscom'] = array(
'url'=>'http://wiscom.fake/api',
'public_key'=>'
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDGUdG5ysZ20qRxvn7aBl9oZCjO
lZ430zwlhyVPGaQAKZ2MKqmwPXZ8wrXQBDo/J9Mc6ecVPiPQN6yVglti1JQ+Psxh
a5zAYkZ5+btPUrUDxvFHgZ0qV9X3ymq2RZQWSejCTSZOghuZulU/IKl+RP6AE3KH
POTFAob5hloT0paVCQIDAQAB
-----END PUBLIC KEY-----'
);