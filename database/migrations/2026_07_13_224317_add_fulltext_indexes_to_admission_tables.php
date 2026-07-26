<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFulltextIndexesToAdmissionTables extends Migration
{
    public function up()
    {
        // 1. Thêm FULLTEXT index cho admission_rag_chunks
        DB::statement('ALTER TABLE admission_rag_chunks ADD FULLTEXT chunks_content_fulltext (content)');

        // 2. Thêm FULLTEXT index cho admission_rag_documents
        DB::statement('ALTER TABLE admission_rag_documents ADD FULLTEXT docs_title_cat_fulltext (title, category)');

        // 3. Thêm FULLTEXT index cho chatbot_tickets
        DB::statement('ALTER TABLE chatbot_tickets ADD FULLTEXT tickets_q_a_fulltext (question, staff_answer)');
    }

    public function down()
    {
        DB::statement('ALTER TABLE admission_rag_chunks DROP INDEX chunks_content_fulltext');
        DB::statement('ALTER TABLE admission_rag_documents DROP INDEX docs_title_cat_fulltext');
        DB::statement('ALTER TABLE chatbot_tickets DROP INDEX tickets_q_a_fulltext');
    }
}
