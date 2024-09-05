import Web3 from 'web3';

const contractAddress = process.argv[3]; // The third element in process.argv is the contract address
const infura = process.argv[2];// The third element in process.argv is the contract address

// console.log(`Started monitoring ERC-1155 contract: ${contractAddress}`);

const web3 = new Web3(new Web3.providers.WebsocketProvider(`wss://mainnet.infura.io/ws/v3/${infura}`));

//0x33cfae13a9486c29cd3b11391cc7eca53822e8c7
// ABI for the ERC-1155 Transfer event
const erc1155TransferABI = [
    {
        "anonymous": false,
        "inputs": [
            {
                "indexed": true,
                "name": "operator",
                "type": "address"
            },
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
                "indexed": false,
                "name": "id",
                "type": "uint256"
            },
            {
                "indexed": false,
                "name": "value",
                "type": "uint256"
            }
        ],
        "name": "TransferSingle",
        "type": "event"
    },
    {
        "anonymous": false,
        "inputs": [
            {
                "indexed": true,
                "name": "operator",
                "type": "address"
            },
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
                "indexed": false,
                "name": "ids",
                "type": "uint256[]"
            },
            {
                "indexed": false,
                "name": "values",
                "type": "uint256[]"
            }
        ],
        "name": "TransferBatch",
        "type": "event"
    }
];

const contract = new web3.eth.Contract(erc1155TransferABI, contractAddress);

web3.eth.net.isListening()
    .then(() => {
        // console.log('Successfully connected to WebSocket provider');

        // Subscribe to TransferBatch events for the ERC-1155 contract
        contract.events.TransferBatch({
            fromBlock: 'latest'
        })
            .on('data', event => {
                // console.log('TransferBatch event:', event.returnValues);
                const { operator, from, to, ids, values } = event.returnValues;
                const output = {
                    transfer: 'batch',
                    contractAddress: contractAddress,
                    from: from,
                    to: to,
                    tokens: ids,
                    values: values
                    // This is now a single concatenated string of messages
                };
                console.log(JSON.stringify(output));
                //
                // console.log(`Batch Transfer from ${from} to ${to} with tokenIds ${ids} and values ${values}`);
            })

        // Subscribe to TransferSingle events for the ERC-1155 contract
        contract.events.TransferSingle({
            fromBlock: 'latest'
        })
            .on('data', event => {
                // console.log('TransferSingle event:', event.returnValues);
                const { operator, from, to, id, value } = event.returnValues;

                const output = {
                    transfer: 'single',
                    contractAddress: contractAddress,
                    from: from,
                    to: to,
                    token: id.toString(), // Convert BigInt to string
                    value: value.toString() // Convert BigInt to string
                    // This is now a single concatenated string of messages
                };
                console.log(JSON.stringify(output));
               // console.log(`Single Transfer from ${from} to ${to} with tokenId ${id} and value ${value}`);
            })



    })
    .catch(e => {
        console.error('Connection error:', e);
    });


// Started monitoring ERC-1155 contract: 0xYourContractAddress
// Successfully connected to WebSocket provider
// TransferBatch event: {
//     operator: '0xoperatoraddress',
//         from: '0xfromaddress',
//         to: '0xtoaddress',
//         ids: [ '1', '2', '3' ],
//         values: [ '100', '200', '300' ]
// }
// Batch Transfer from 0xfromaddress to 0xtoaddress with tokenIds 1,2,3 and values 100,200,300


// Started monitoring ERC-1155 contract: 0xYourContractAddress
// Successfully connected to WebSocket provider
// TransferSingle event: {
//     operator: '0xoperatoraddress',
//         from: '0xfromaddress',
//         to: '0xtoaddress',
//         id: '1',
//         value: '100'
// }
// Single Transfer from 0xfromaddress to 0xtoaddress with tokenId 1 and value 100
