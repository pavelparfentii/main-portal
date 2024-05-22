import Web3 from 'web3';
console.log("Loaded")
// Ensure you are using the correct Web3 version
console.log(`Web3 version: ${Web3.version}`);

let web3 = new Web3(new Web3.providers.WebsocketProvider("wss://mainnet.infura.io/ws/v3/40892bf6b52d4e1b81daf162c9029d93"));

// const contractABI = [
//     {
//         "anonymous": false,
//         "inputs": [
//             {
//                 "indexed": true,
//                 "name": "from",
//                 "type": "address"
//             },
//             {
//                 "indexed": true,
//                 "name": "to",
//                 "type": "address"
//             },
//             {
//                 "indexed": false,
//                 "name": "value",
//                 "type": "uint256"
//             }
//         ],
//         "name": "Transfer",
//         "type": "event"
//     }
// ];

const erc721TransferABI = [
    {
        "anonymous": false,
        "inputs": [
            {
                "indexed": true,
                "name": "from",
                "type": "address"
            },
            {
                "indexed": true,
                "name": "to",
                "type": "address"
            },
            {
                "indexed": true,
                "name": "tokenId",
                "type": "uint256"
            }
        ],
        "name": "Transfer",
        "type": "event"
    }
];

// Address of the contract
// const contractAddress = '0x1f9840a85d5af5bf1d1762f925bdaddc4201f984'; // Replace with your contract address
// const contractAddress = '0x7f36182dee28c45de6072a34d29855bae76dbe2f';
const contractAddress = '0x60e4d786628fea6478f785a6d7e704777c86a7c6';

//this CODE IS WORKING

const contract = new web3.eth.Contract(erc721TransferABI, contractAddress);


web3.eth.net.isListening()
    .then(() => {
        console.log('Successfully connected to WebSocket provider');

        // Subscribe to Transfer events for the NFT contract
        contract.events.Transfer({
            fromBlock: 'latest'
        })
            .on('data', event => {
                console.log('Transfer event:', event.returnValues);
                // Accessing specific event return values
                const { from, to, tokenId } = event.returnValues;
                console.log(`NFT Transfer from ${from} to ${to} with tokenId ${tokenId}`);
                // You can add additional processing here
            })
            .on('error', err => {
                console.error('Error:', err);
            });
    })
    .catch(e => {
        console.error('Connection error:', e);
    });

// const contractAddresses = [
//     '0x7f36182dee28c45de6072a34d29855bae76dbe2f', // Replace with your first NFT contract address
//     '0x60e4d786628fea6478f785a6d7e704777c86a7c6',
//     '0x7aada103f7852c7e7da61e100d6277a3fd199b58',
//     '0x1b41d54b3f8de13d58102c50d7431fd6aa1a2c48',
//     '0x670d4dd2e6badfbbd372d0d37e06cd2852754a04',
//     // Add more addresses as needed
// ];
//
// web3.eth.net.isListening()
//     .then(() => {
//         console.log('Successfully connected to WebSocket provider');
//
//         // Subscribe to Transfer events for each NFT contract
//         contractAddresses.forEach(contractAddress => {
//             const contract = new web3.eth.Contract(erc721TransferABI, contractAddress);
//
//             contract.events.Transfer({
//                 fromBlock: 'latest'
//             })
//                 .on('data', event => {
//                     console.log(`Transfer event from contract ${contractAddress}:`, event.returnValues);
//                     // Accessing specific event return values
//                     const { from, to, tokenId } = event.returnValues;
//                     console.log(`NFT Transfer from ${from} to ${to} with tokenId ${tokenId}`);
//                     // You can add additional processing here
//                 })
//                 .on('error', err => {
//                     console.error(`Error in contract ${contractAddress}:`, err);
//                 });
//         });
//     })
//     .catch(e => {
//         console.error('Connection error:', e);
//     });
