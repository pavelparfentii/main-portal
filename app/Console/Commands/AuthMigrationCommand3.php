<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Week;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AuthMigrationCommand3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auth-migration-command3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $authorizationToken = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJlNDMwMDliNi0yYWYzLTQ4NDQtYTk5NC1lZWZiOWY4ZTgwOGUiLCJleHAiOjE3MTgxOTkyODUsIm5hbWUiOiJQYXNoYSIsImVtYWlsIjoicGF2ZWxwcmZudEBnbWFpbC5jb20iLCJ3YWxsZXRfYWRkcmVzcyI6IjB4MjliOTIxN2Q1YjA0NWYwNEY1OEY2NDFFOEVkQmQ4YkVmY2M2MTQ5YyIsImlzX2FkbWluIjp0cnVlLCJkaXNjb3JkIjp7InByb3ZpZGVyX2lkIjoiOTgzODAwNDk4NTY4MTk2MDk3IiwidXNlcl9uYW1lIjoiYnJlaW42ODA0In0sInR3aXR0ZXIiOnsicHJvdmlkZXJfaWQiOiIxNDk2ODIwMzk2NTAyNDMzNzk3IiwidXNlcl9uYW1lIjoicGhwX2FydGlzYWgiLCJuYW1lIjoicGhwIGFydGlzYW4iLCJwcm9maWxlX2ltYWdlX3VybCI6Imh0dHBzOi8vcGJzLnR3aW1nLmNvbS9wcm9maWxlX2ltYWdlcy8xNDk2ODIwNDUzMTc1OTY3NzQ5L2lQZ2tmazV6LnBuZyJ9fQ.JI-UewonSkAsW_2l9BuveOoHjlgwRYdTwHNCt9NQgaQ';

        $endpointUrl = 'https://auth.digitalsouls.club/management/users/';

        $array = Account::whereNotNull('auth_id')->pluck('auth_id')->toArray();
       // $array = ["646f9fb8-9e1f-49b2-84db-66f62cac5cdf", "8fa0136e-662d-4040-81eb-3390d8b07b73", "4a18daa0-b205-4d6d-a494-045c1b37c7d9", "6865d093-8044-4236-a443-a3f894268e08", "1313307c-b8d2-4314-948a-b06fef7430a8", "9f7a4d07-dd02-4497-8109-da94c2d7d555", "9e69310a-2c29-49a3-819b-14812531518b", "ff6c098f-c976-4996-bb45-3a99116075d5", "db0bb185-3980-4e99-a138-ae5cd75cd520", "d1d30996-3c5d-47ef-8a0a-abcb2b127d63", "2cb1b6f1-8922-4f66-9f8d-cf5818b848ed", "288b89c9-0dd9-4a98-97b3-1545e3dc0cc3", "eb95a200-cd85-4177-974f-c029688c7bd3", "e44a792b-19bf-409c-805d-5c1aa10d2ea0", "55c6cace-50bc-4f1d-8ee6-9356e8872161", "f6776fba-2961-4b4b-bab3-c98d938af672", "5aeb1042-2dab-4737-a848-17df28c3a97a", "dfa7a48b-aa22-4898-98f4-0091565c9931", "3fd459c8-d21a-4746-b915-7d83d8a6a4b0", "d1baef95-7506-45da-829b-3593ce5a9c03", "9b769c52-c7bd-406d-a656-b92d72c38956", "cac33084-616b-4c63-9c27-d18205d18fdf", "4ccbdead-3410-45e9-b1ed-3761b4c82d7a", "5d5c10ca-3046-4973-aef9-7b399f5d196c", "92d9db78-de63-4170-b490-15551802e0d4", "b810d5ee-fbe2-46c7-a6f4-5180a174e9d0", "a2d3677e-2fb8-4c63-b78d-12c4b4c3a937", "49c7f177-cbef-4870-a98c-5bc48a22172b", "d56b083b-c5f6-4d2a-90b2-041e5b8cf787", "363fb8c7-2202-40b6-97e5-0a2ba0d350b9", "12edbdc3-abd2-4259-916f-10cde13aa395", "6878055c-4b60-4db3-bc9d-19073ce2772e", "a9035358-6936-4933-a388-970cf7bb1f80", "3147846c-0395-45e3-8b9b-bc921c3f4746", "02ab6191-04cb-40f3-8e11-2809788ddbcb", "61725faf-38c4-43a8-ada3-3c0c628964bb", "edce1208-ef6d-402d-ad98-27ad025ad69c", "9c92375f-0d87-4843-b04b-ca7a1b3e0084", "ccff47cb-0a01-4076-b19d-af8b0c3989ae", "1ef64eff-5490-49e0-a492-5f5e3f828216", "dac7bc82-c4a1-4711-8796-e3d7b312ca16", "a05b233a-93dc-4dc7-8545-271a33dacf7e", "51637a45-586a-44fc-a70c-67c6ae8af291", "439fe96b-7519-474a-81f4-0d554f023325", "0a9d222e-b95b-4fa0-a62c-702f05e5bec0", "d0a964c2-cab2-4949-a6b1-48062053c657", "d8278490-6ec7-47ed-8a44-884b9926705f", "e284bfe5-d046-425f-9cae-e05a2b69b6dc", "46a9dbc6-1af8-4913-a556-b985ae6efb79", "9f1e44d2-6f03-4dd8-a32b-e79eb78a3ace", "eecde5dd-5338-459f-ba7d-a18de31e8082", "16418396-4d7a-4750-b845-7c97bb7132b7", "e0915f99-3ea0-4ef9-8c72-70aecd8ad5a2", "a0f20973-0369-4f76-8b01-34550887f177", "d1b4b1eb-76cd-484b-9967-a1f1d977dd25", "0b62aece-8432-4977-a906-37d8e5ad69de", "e173e4a4-eec9-4492-83a3-fffb31bc4e5e", "7cb49a5c-cb25-4a80-b6f4-090b96eeceb6", "f8375bd6-1f05-4906-931e-9492b7a15f9c", "222dc5e6-140b-4fce-bb1f-a708c0fc40c4", "db3408e6-0e48-4ebd-b9d4-2f41a772d506", "01b9b560-36f9-4de5-b720-9ae0d486c6f8", "336beb16-d3b5-4079-af9a-4e703fcd72e4", "1ff4c53a-57cc-4524-a585-96051eb1ff2b", "df8197bb-226c-49ce-bd25-5ebf299e3f75", "f293ae0d-07c7-4162-b833-55c32fe4297f", "75aae44a-ceb6-4281-ba5c-33d6e34c6f50", "970c7cc5-f848-4f21-9858-ea7cd78663c1", "d85158ba-5159-4897-9111-ada3d2d8da99", "23bedbeb-3b85-4798-b8a4-15905c937cd3", "1049c9c9-68ee-479c-b531-8a2e3708307c", "b2876820-5181-47e3-8a3d-a4f19790621c", "e4128909-7318-4480-8a98-311b909139c8", "89324d65-da07-43b2-9042-5c6bc0665197", "7201c0ad-bcd2-497b-ade9-279bb855a2af", "bc37287c-095a-4fff-b1c1-a142ee554fb5", "102b5e73-9e97-4cb7-8f9f-6c6bae1228d4", "a542bc1f-a815-400b-879d-95cacd0961e7", "bd429513-7071-4872-869d-fc4250da4cd6", "64e9974b-12e1-401e-aa28-95719f987620", "8488a360-8499-4e6b-96fa-35203f5ee60b", "9906f114-fe9b-435e-a5be-75242bc41d8d", "b5313d97-f329-48b5-9bbf-46ed3117fe70", "f5c65f6c-298c-47ea-bc9f-fd2cc4abc150", "af5532db-1d64-4f9d-b1fa-0c69d91b0568", "89b01b2a-25e1-4806-820e-c51367693ff1", "d13d7766-4dd1-4222-ac4a-93aef8a9820c", "050b49f2-7b5e-4fd6-af25-6180ae268e78", "084d2f3b-999a-4779-a57d-5a9b2fcfca56", "7cf27fad-39ae-457a-b6e8-e01bb5a65c5e", "1e315f0b-3ee4-413f-b5c3-c44b57a8481c", "05cb5181-6164-44ca-ba96-d098be9f3453", "02ae307b-0e52-4f41-bf16-9c69b9701b46", "89b8344e-1f61-4a82-8d96-2653de6d7763", "019b9c48-133f-4b01-8483-3fd6eedccdf3", "645af979-ec8d-4c72-9189-90e0dbecb3de", "8c1104c6-9805-4724-a1fa-1943d1555aa0", "6c4fe49d-8162-4d2e-9416-a8e8df011bf3", "f15f07bc-5924-4600-a4a9-9707f0b800a5", "dceaa17f-4c9a-4f4d-a0fd-d3b8729cae3e", "2a9e4a64-4f2b-4b4a-b9e5-4889ff12b3c0", "fb8b4b0b-c1a4-4f54-995f-6200420922e2", "96eb3898-ffe3-44e6-8206-73a18102b8af", "d81f8c0d-b654-4199-bcfe-714b2e6161bf", "0df7d810-ec80-47b0-861c-b87c645ad784", "29329a9d-d17b-4413-a42c-d02b33436354", "d1b9aa65-8133-4343-9dba-a1b9d6546728", "dfd15d3d-64e0-4470-be7e-15a9e2222e29", "2afa542d-5271-48b1-a531-90e949d1b314", "979be569-fb1e-40fb-b01e-4125c0345546", "42bb5bb0-dcd6-41cd-9e6f-9c9773388f1a", "0f8e166d-01b5-4343-927c-839ba72d6ffe", "71473736-6f21-4750-9983-d60a07008679", "80b39f3f-124c-4217-9b46-35e06f0e01d8", "bb649500-ec23-4be8-8cec-e3918a614d6a", "e892675f-6f83-4863-89aa-8671bec56db0", "a5ae225a-4ff4-4a09-bd2a-6032a7e5707d", "03f8cedb-d6cc-485e-8f21-37d6193c6bc6", "2b3ab6f3-505f-4673-9a94-e87ba0236ae4", "752cf252-f932-40fa-ae47-73167f5e8956", "386a56a1-3d85-45ec-b7a1-7a12398f09d0", "109ff10b-25c9-4278-9d3e-d976fed55a07", "e2e22c33-d38b-42a5-ac62-b4c2ec03b93d", "131945fa-b93d-4742-b913-8082b8eae8ee", "623160eb-84a4-448b-ab5b-860fcd18cb07", "5aee9df3-9027-4920-8386-914efaa097d0", "69025881-dd5b-4428-8578-18d48694b40a", "501b822a-b3c3-440b-81df-c81316dea2a5", "fd9afe1a-a113-4bd7-bb4a-aa95cb8a3080", "9ade655e-690c-4db4-a1f3-2efe70715e23", "1f5258de-0184-4074-b067-1ff3bf2a93f8", "d5a26d94-bc3a-4574-9a21-64251249dd89", "96d3521b-4c16-49d3-b5a4-9c642f694308", "aeb217b0-a601-4582-9a6b-dd4a992f709a", "0809495f-6a74-4c78-a892-9a0ce6f7c285", "6ccfddaf-94f2-467c-a633-f4e747895ba9", "6046c0a3-b75b-4d8b-a21f-9fff10da48bd", "1f7c9bdb-367c-434e-b338-3ca78cfe0f3d", "025c3305-bf7b-4994-9c7e-7f4e2337e448", "f0ce9107-0660-4775-9441-ac7fc40b04f1", "0d088cb4-a867-425a-9c6c-9427ef9b6533", "a69e0d92-4347-41d7-8d63-84671de7b5d2", "daf36aca-ea53-437e-90a6-e9321d51259e", "6a511c9d-e9c5-4971-8bb7-0665656fd78e", "0be9ccac-2091-474b-923e-fd0a6a9aa8cb", "b2c07783-49ff-4709-b65b-7fbc4e9c43f1", "f16490af-5af0-406b-bb3c-61f2ec6f0f24", "73a619d2-cd85-4fd6-8b9c-a86500f96e9e", "3553877f-b0da-45ec-ba7c-19ba53b32f93", "01e36ec9-0e1d-475b-9bfd-7543dfa2541d", "d9d8a069-fc34-4975-820a-d8beb218c90a", "a51f5549-fa58-405e-a8a1-0ad9261a1d5a", "22e9c5c3-3672-43d0-b7d6-47b83f2e1e1a", "287c3118-b926-4bf5-a173-33e7f9140f44", "513cc54c-e571-4cbb-9c69-30561b80f89c", "a48e8098-a5ba-43ac-92b3-53ba26d61482", "15aacf88-432e-4870-b3fc-ab5bbfbe0462", "8e6b845e-db13-45b3-8fed-32f49b002920", "18bd64e3-67ab-427b-b061-7846d7a2c317", "23b9c346-cc27-4ca0-a179-a551cc1e021f", "dc2ca1e1-63e4-49d2-a382-9532bb3067d3", "cf1e069b-0930-4b90-bba4-f40dc1813f3f", "62e9812e-94ac-4d0b-ba0f-42081a432d74", "bd377a86-cae2-4723-9a5a-f1b96683b2c7", "a3c9a064-a002-44db-a91d-a2f7ec6ea20a", "52baef46-4290-458c-a174-4705ff8e2a6a", "e9faefbc-fe79-4a01-8d1e-32de19eaa9f2", "17a22e44-20df-4460-831a-2c73f916870b", "f7e71662-d748-4a9b-9e81-36c5e4295067", "3718a6cc-1af0-4016-ba89-4cf95c444c98", "54e0c819-7e09-45be-a627-63b04cc1abd6", "dda6171d-6573-45c0-b689-68b9afbf23b1", "ea944e10-5bf0-4eb3-8c65-ea583edc66fd", "565200c9-7c2d-4080-b864-130351732fc2", "de404720-f9c6-47a5-81b2-92abc416ac32", "2c843999-7ca9-45a1-94e6-e0e1b3c1d0d0", "3bbbf14b-4f5a-46d7-93fb-0ae83f167aae", "7b946287-bc9b-42d8-9c1c-e9deef86231a", "bd3bd6ee-00ce-4961-9868-a52a8adf6760", "46ea57ad-4d4a-47b3-ac4b-eeb7dc0230ff", "32c3c6f9-4d79-4801-b684-1de09c13c582", "11cbc6d4-8ea3-4d66-9efe-df49c9c003a0", "ef4dd346-7b47-48b9-a35a-7a4f10e39fb4", "7ca30c9e-f9f2-4181-893b-5d3be522cdf8", "041bbd95-5818-4440-ba63-652558a5bace", "a9fc00a7-6d2f-41f1-906c-d6c6a02e4b16", "9104f907-1a68-45ff-af17-90195a4c8919", "65bb6842-dca7-4dfd-b3b0-08b2e73a9a08", "37a4f17e-7d0a-48fd-968b-c8e490eb9c2a", "1f12df49-58e6-4ee0-8e0f-1a9190506d4d", "2a07df02-ec74-4ff5-9933-e94279d4e828", "d51114fd-0200-496e-8e80-89ffeb8cca15", "e5f00cbf-f01f-494a-a022-1e108cc438e9", "f052a9b8-6168-4be1-ab2c-8be131ce6993", "e67cee58-75ef-4d3a-a385-0b918c85c265", "41922667-031b-4aba-892c-c228a45d1259", "7cd3ad08-802d-4aa1-ac77-7460d80a04f5", "dde4e078-3688-4d5f-9223-764d95f77edc", "352d7bce-8d5f-47b6-8008-626b697f4eb0", "d72d7222-875d-461f-b00a-02725eff80a7", "91ad3731-f0df-41c3-8662-c30994c69bbc", "9d5c81e0-7d80-4715-92f5-89a715142d80", "7d1ed80d-9dd7-4783-9056-33912b98d68b", "992fe7e7-bf09-48fe-ba66-a407a943e06c", "86060e41-755c-42af-83c0-cb53dc40819a", "1e918968-e7d3-4f62-89f7-c9ce65830cae", "f37966a2-0bde-4add-a38f-0a80a5f278ee", "c1bf6483-69b0-4853-a48c-b03f1c7ab90b", "29cd06ee-a671-4af7-8315-9e20ce3e3a18", "63ca7677-6d48-4c14-9501-38e6cb3ea32f", "dbf963f1-5e0e-4f66-af74-dd29e0e9c966", "a15e1982-081d-43ac-a585-fa5433327d7a", "205df780-0147-4483-8c0a-e8b293c5a29d", "4e745328-defa-49f0-ad04-ec5d9eab4e6d", "f99848a4-a3d1-4f63-8772-d80f099f1ee0", "03748b02-786f-4f41-9691-ed65087776ef", "8700ed56-91b7-4374-8e63-4d3a02b06ada", "38c72e69-f6c2-4fa8-9615-754dd9f07ea6", "5feab60a-522e-4f7a-ac33-84266613d0c0", "46a7df43-68dd-47bd-b9b5-29d8e47d6410", "bd6e8dfc-f168-4a0e-b4a0-04a2a2439d2c", "04a6599d-c774-4787-b1a1-a14fd62ca4ea", "93635947-baa7-47dd-a733-8f946d8d9b23", "feaddd38-1c13-48fe-a844-e67d67aa438c", "86ae1754-7102-4606-9b11-0e90d4eb08e8", "32c79d31-61d2-40c5-87e3-4d0eab643621", "0cf80260-4792-4bdc-9bf9-a76cf98649fc", "e859b935-cb58-4702-a601-c19d9cc873e6", "bcf3e5ae-f700-4a26-aed6-6f613a645e89", "04cb16cb-7f04-4a07-95e6-ba9ea33795d0", "1a46ba12-e7e9-4411-b7b8-292cd68d7929", "659ba424-0eda-4665-9e92-488cf3030906", "6da035d7-bc76-42c5-96f2-38f25e869354", "7305733b-a8cb-4cca-8235-222fae68d593", "9fbbb46a-27cd-43bc-9ae7-5509d7a7e57f", "c8552d8a-72df-4c5b-92ac-fc1a44b114d2", "2644c09c-a7df-447b-9130-51f85eed7bf1", "0d038926-c55b-4397-b990-f0b5bb9ae7ed", "10ee2d32-7df7-4a34-81a5-89c25eeef1f2", "63039438-e3e3-4e1b-ad05-e987bc971397", "467d261d-f5f2-45c7-8cd2-a1d38a282614", "409f968f-1de4-42e0-a0b5-482aa37537a9", "fe707125-ee97-4fc0-b1b1-771126c902ae", "590a4c33-0f92-4da2-835f-e8ec2108c1a8", "1412bddc-f57b-47c9-8a5d-c768a5e69caa", "8fd7e854-6633-4ee4-9bd0-565e5abd9f59", "897d2421-07c1-4bd5-802f-876b2991d7df", "a229402d-dd80-4fe8-909c-919e27983981", "cee854eb-5afc-4d29-88d8-27cd6f12193d", "68a3f240-9d00-4344-9c5a-483609075041", "0b83be36-489f-4ab3-b556-4f61c6d5144e", "409dba62-1edc-40b9-a7cc-157db255eefb", "99399e9e-b0c3-481b-afc8-c775caf0578a", "a1e097a9-3aae-43c6-9daf-dbddfd41c16d", "91dd7f82-6db1-4e03-9159-8e7da572db45", "30341ee6-c83c-441b-a5bf-136ac06f9e5e", "cf50fbce-d0e4-44ca-bcf4-506fe389a7c0", "0763ba8c-c39f-4dc0-a82d-87063145bfd2", "ec3c7a17-0d08-4413-aab2-8420dacf04da", "96eb4ac9-dced-49cd-88d1-03792c760b2a", "efa492a8-83c9-463c-98a3-ffcb7f9e9ecf", "e62452f2-0e07-48e4-98e5-b5ac6d07965b", "23a0fde8-a2c1-4061-bc8f-088686e7a6d8", "019db0c7-c04c-4785-b66f-b8c366ad458a", "24fb4b8a-97bb-4265-8939-614f653f367c", "fd22876f-d084-4f7a-a868-af98c402e520", "10ea785d-b0c2-44fd-84a9-53f54fa70db6", "7606bd04-2217-4e1b-a594-ba2c28e9e9dc", "31e4c08f-efb5-4cdd-b23f-3afa4eed05ce", "715f877d-926c-49a9-95b6-cbdbc693a7bf", "99c0dbd3-3a91-4a9d-b94c-383859b0110e"];
        $array = ['99f4db29-cea4-4425-888f-6d6fdacb6ab2', '4bf9cd78-cd91-4f23-817d-123871b3bda9'];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $authorizationToken
        ])->withBody(json_encode($array), 'application/json')->get($endpointUrl);

        if ($response->successful()) {
            $usersData = $response->json();

            $bar = $this->output->createProgressBar(count($usersData['users']));
            $bar->start();
//            dd($usersData);
            foreach ($usersData['users'] as $datum){

                $bar->advance();

                $account = Account::where('auth_id', $datum['uuid'])->first();
                if($account){
                    $account->update(['email'=>$datum['email']]);

                }else{

                    var_dump('here');
                    $userWallet = !is_null($datum['wallet_address']) ? strtolower($datum['wallet_address']) : null;
                    $TwitterId = !is_null($datum['twitter']) ? $datum['twitter']['id'] : null;
                    $TwitterName = !is_null($datum['twitter']) ? $datum['twitter']['name'] : null;
                    $TwitterUsername =!is_null($datum['twitter']) ? strtolower($datum['twitter']['user_name']) : null;
                    $TwitterAvatar = !is_null($datum['twitter']) ? $datum['twitter']['profile_image_url'] : null;
                    $discordUserName = !is_null($datum['discord']) ? $datum['discord']['user_name'] : null;
                    $discordId = !is_null($datum['discord']) ? $datum['discord']['id'] : null;


                    $account = new Account();
                    $account->auth_id = $datum['uuid'];
                    $account->wallet =  $userWallet;
                    $account->twitter_username = $TwitterUsername;
                    $account->twitter_id = $TwitterId;
                    $account->twitter_name = $TwitterName;
                    $account->discord_id = $discordId;
                    $account->save();
                    Week::getCurrentWeekForAccount($account);
                    $account->twitter_avatar = $account->downloadTwitterAvatar($TwitterAvatar);
                    $account->save();
                }
                $bar->finish();
            }

        }

        $this->info('success');
//        dd($response->body());
    }
}
