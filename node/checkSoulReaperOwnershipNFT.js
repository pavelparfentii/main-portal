import Web3 from "web3";
import fetch from 'node-fetch';

const user = process.argv[2]; // The wallet address to check ownership against

async function fetchApiKey() {
    const response = await fetch('https://safesoul.test-dev.site/api/airdrop/infuraKey');
    const data = await response.json();
    return data.key; // Make sure this matches the actual key name in your JSON response
}

async function getWeb3WithNextApiKey() {
    try {
        const nextApiKey = await fetchApiKey();
        return new Web3(
            new Web3.providers.HttpProvider(`https://mainnet.infura.io/v3/${nextApiKey}`)
        );
    } catch (error) {
        console.error('Failed to create web3 instance with the next API key:', error);
        throw error;
    }
}

const nfts = [
    { contractAddress: '0x3491ead95de2f4a945c2ead3c3afe7747f2ae373', tokenId: '3' },
    { contractAddress: '0x09e8dc2fee2e6be7687ae44b1381dfd2e0c13465', tokenId: '3' }
];

const ownerOfAbi =
    [{
        "inputs": [
            {
                "internalType": "address",
                "name": "account",
                "type": "address"
            },
            {
                "internalType": "uint256",
                "name": "id",
                "type": "uint256"
            }
        ],
        "name": "balanceOf",
        "outputs": [
            {
                "internalType": "uint256",
                "name": "",
                "type": "uint256"
            }
        ],
        "stateMutability": "view",
        "type": "function"
    }];


async function checkNFTOwnership(web3Instance, contractAddress, tokenId) {
    const contract = new web3Instance.eth.Contract(ownerOfAbi, contractAddress);
    try {
        const ownerTokenBalance = await contract.methods.balanceOf(user, tokenId).call();
        // console.log(Number(ownerTokenBalance));
        return Number(ownerTokenBalance);
    } catch (error) {
        console.error(`Failed to check ownership for token ${tokenId} at contract ${contractAddress}:`, error);
        return false;
    }
}

async function main() {
    const web3Instance = await getWeb3WithNextApiKey();
    let ownershipResults = []; // To hold individual ownership messages
    let totalOwned = 0; // To count how many NFTs the user owns across checked contracts

    for (const nft of nfts) {
        const ownerTokenBalance = await checkNFTOwnership(web3Instance, nft.contractAddress, nft.tokenId);
        if (ownerTokenBalance > 0) {
            totalOwned+=ownerTokenBalance;
            ownershipResults.push(`User owns SoulReaper Pass ${ownerTokenBalance} NFT ${nft.tokenId}/${nft.contractAddress}`);
        } else {
            ownershipResults.push(`User ${user} does not own NFT ${nft.tokenId}/${nft.contractAddress}`);
        }
    }

    // Concatenate all messages into a single string
    const allMessages = ownershipResults.join(', ');

    // Prepare the final output object including all messages and total ownership count
    const output = {
        totalOwned: totalOwned,
        message: allMessages // This is now a single concatenated string of messages
    };
    console.log(JSON.stringify(output));

}

main();
