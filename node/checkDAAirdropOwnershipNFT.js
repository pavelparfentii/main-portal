import web3 from "web3";
import fetch from 'node-fetch';
const user = process.argv[2];

async function fetchApiKey() {
    const response = await fetch('https://safesoul.test-dev.site/api/airdrop/infuraKey');
    const data = await response.json(); // Assuming the key is returned in JSON format
    // console.log(data);
    return data.key; // Adjust according to your actual API response structure
}

async function getWeb3WithNextApiKey() {
    try {
        const nextApiKey = await fetchApiKey();
        return new web3(
            new web3.providers.HttpProvider(
                "https://mainnet.infura.io/v3/" + nextApiKey
            )
        );
    } catch (error) {
        console.error('Failed to create web3 instance with the next API key:', error);
        throw error;
    }
}

let tokens = [43, 42, 41, 46, 1, 2, 24, 33, 4, 22, 30, 10, 20, 29, 39, 32, 36, 35, 27, 14, 15, 26, 23, 13, 37, 38, 17, 18, 40, 7, 19, 34, 11, 3, 31, 25, 28, 16, 21, 5, 8, 6, 12, 9];

let contractABI = [
    {
        inputs: [
            {
                internalType: "address",
                name: "account",
                type: "address"
            },
            {
                internalType: "uint256",
                name: "id",
                type: "uint256"
            }
        ],
        name: "balanceOf",
        outputs: [
            {
                internalType: "uint256",
                name: "",
                type: "uint256"
            }
        ],
        stateMutability: "view",
        type: "function"
    },
    {
        inputs: [
            {
                internalType: "address[]",
                name: "accounts",
                type: "address[]"
            },
            {
                internalType: "uint256[]",
                name: "ids",
                type: "uint256[]"
            }
        ],
        name: "balanceOfBatch",
        outputs: [
            {
                internalType: "uint256[]",
                name: "",
                type: "uint256[]"
            }
        ],
        stateMutability: "view",
        type: "function"
    },
];

async function initializeContract() {
    const web3Instance = await getWeb3WithNextApiKey(); // Await the web3 instance
    const contract = new web3Instance.eth.Contract(
        contractABI,
        "0xd0888b57f3b760129ad6b2f750e30f3f7760ade9"
    );
    return contract;
}

async function loadUserTokens() {
    // for (const token of tokens) {let sum = 0;
    let sum = BigInt(0);
    let details = [];
        try {
            let contract = await initializeContract(); // Await the contract initialization
            // let userTokensCount = await contract.methods.balanceOfBatch([user], tokens).call();

            const userAddresses = new Array(tokens.length).fill(user);
            // Convert token IDs to strings
            const tokenIds = tokens.map(String);

            let userTokensCount = await contract.methods.balanceOfBatch(userAddresses, tokenIds).call();
            // const tokensPromises = Array.from({ length: Number(userTokensCount) }, (_, index) =>
            //     contract.methods.balanceOf(user, index).call()
            // );
            //
            // const tokens = await Promise.all(tokensPromises);
            // const tokenNumbers = tokens.map(token => parseInt(token, 10));

            userTokensCount.forEach(tokenCount => {
                sum += BigInt(tokenCount);
            });

            let tokenDetails = userTokensCount.map((balance, index) => {
                return `tokenId${tokens[index]}: ${balance}`;
            }).join(', ');

            // console.log(`Wallet ${user} owns: ${tokenDetails}`);

            const output = {
                totalOwned: Number(sum),
                message:`Wallet ${user} owns: ${tokenDetails}` // This is now a single concatenated string of messages
            };
            console.log(JSON.stringify(output));
            // console.log(userTokensCount);
        } catch (error) {
            console.error('Failed to load user tokens:', error);
        }
    // }

}

loadUserTokens();
